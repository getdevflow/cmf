<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Application\Devflow;
use App\Shared\Services\PhpFileParser;
use Codefy\Framework\Factory\FileLoggerFactory;
use Exception;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Ulid;
use ReflectionException;

use function Codefy\Framework\Helpers\public_path;
use function dirname;
use function glob;
use function is_string;
use function ltrim;
use function preg_quote;
use function preg_replace;
use function Qubus\Support\Helpers\add_trailing_slash;
use function Qubus\Support\Helpers\is_false__;
use function sprintf;
use function trim;

/**
 * Extracts the file name of a specific plugin.
 *
 * @param string $filename Plugin's file name.
 */
function plugin_basename(string $filename): string
{
    $file = normalize_path($filename);

    $pluginDir = normalize_path(public_path('plugins'));

    $file = preg_replace(
        pattern: '#^' . preg_quote($pluginDir, delimiter: '#') . '/|^/#',
        replacement: '',
        subject: $file
    );

    $filename = trim($file, '/');
    return \basename($filename);
}

/**
 * When a plugin is activated, the action `activate_name` hook is called.
 * `name` is replaced by the actual file name of the plugin being activated.
 * So if the plugin is located at 'public/plugins/SamplePlugin.php', then the hook will
 * call 'activate_Sample'.
 *
 * @param string $filename Plugin's filename.
 * @param mixed $function The function that should be triggered by the hook.
 * @throws ReflectionException
 */
function register_activation_hook(string $filename, mixed $function): void
{
    $pluginName = plugin_basename($filename);
    Action::getInstance()->addAction(hook: "activate_{$pluginName}", callback: $function);
}

/**
 * When a plugin is deactivated, the action `deactivate_name` hook is called.
 * `name` is replaced by the actual file name of the plugin being deactivated.
 * So if the plugin is located at 'public/plugins/SamplePlugin.php', then the hook will
 * call 'deactivate_Sample'.
 *
 * @param string $filename Plugin's filename.
 * @param mixed $function The function that should be triggered by the hook.
 * @throws ReflectionException
 */
function register_deactivation_hook(string $filename, mixed $function): void
{
    $pluginName = plugin_basename($filename);
    Action::getInstance()->addAction(hook: "deactivate_{$pluginName}", callback: $function);
}

/**
 * Get the filesystem directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @param string $filename The filename of the plugin (__FILE__).
 * @return string The filesystem path of the directory that contains the plugin.
 */
function plugin_dir_path(string $filename): string
{
    return add_trailing_slash($filename);
}

/**
 * Returns a list of all plugins that have been activated.
 *
 * @return array|false
 * @throws ReflectionException
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function active_plugins(): array|false
{
    $dfdb = dfdb();

    try {
        return $dfdb->getResults(query: "SELECT * FROM {$dfdb->prefix}plugin");
    } catch (PDOException $e) {
    }

    return false;
}

/**
 * Activates a specific plugin that is called by $_GET['id'] variable.
 *
 * @param string $plugin ID of the plugin to activate
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function activate_plugin(string $plugin): void
{
    $dfdb = dfdb();

    try {
        $dfdb->qb()->transactional(function () use ($dfdb, $plugin) {
            $dfdb->qb()->table(tableName: "{$dfdb->prefix}plugin")->insert(
                data: ['plugin_id' => Ulid::generateAsString(), 'plugin_classname' => $plugin]
            );
        });
    } catch (PDOException | Exception $ex) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'PLUGINACTIVATE[insert]: %s',
                $ex->getMessage()
            ),
            [
                'plugin' => 'activate'
            ]
        );
    }
}

/**
 * Deactivates a plugin.
 *
 * @param string $plugin
 * @return void
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function deactivate_plugin(string $plugin): void
{
    $dfdb = dfdb();

    try {
        $dfdb->qb()->transactional(function () use ($dfdb, $plugin) {
            $dfdb->qb()
                    ->table(tableName: "{$dfdb->prefix}plugin")
                    ->where(condition: 'plugin_classname = ?', parameters: $plugin)
                    ->delete();
        });
    } catch (PDOException | Exception $ex) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'PLUGINDEACTIVATE[delete]: %s',
                $ex->getMessage()
            ),
            [
                'plugin' => 'deactivate'
            ]
        );
    }
}

/**
 * Checks if a particular plugin has been activated.
 *
 * @param string $plugin
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws \Qubus\Exception\Exception
 */
function is_plugin_activated(string $plugin): bool
{
    $dfdb = dfdb();

    $prepare = $dfdb->prepare(
        query: "SELECT COUNT(*) FROM {$dfdb->prefix}plugin WHERE plugin_classname = ?",
        params: [
            $plugin
        ]
    );
    $count = $dfdb->getVar($prepare);

    return $count > 0;
}

/**
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 */
function load_active_plugins(): void
{
    $activePlugins = active_plugins();

    if (!is_false__($activePlugins)) {
        foreach ($activePlugins as $plugin) {
            Devflow::inst()::$APP->execute([$plugin->plugin_classname, 'handle']);
        }
    }
}

/**
 * Retrieves a URL within the plugins or mu-plugins directory.
 *
 * Defaults to the plugins directory URL if no arguments are supplied.
 *
 * @param string $path  Optional. Extra path appended to the end of the URL, including
 *                      the relative directory if $plugin is supplied. Default empty.
 * @param string $plugin Optional. A full path to a file inside a plugin or mu-plugin.
 *                       The URL will be relative to its directory. Default empty.
 *                       Typically, this is done by passing `__FILE__` as the argument.
 * @return string Plugins URL link with optional paths appended.
 * @throws ReflectionException
 * @throws \Qubus\Exception\Exception
 */
function plugin_url(string $path = '', string $plugin = ''): string
{
    $path = normalize_path($path ?? '');
    $plugin = normalize_path($plugin ?? '');

    $pluginUrl = site_url('plugins/');

    $url = set_url_scheme($pluginUrl);

    if (!empty($plugin) && is_string($plugin)) {
        $folder = plugin_basename(dirname($plugin));
        if ('.' != $folder) {
            $url .= ltrim($folder, '/');
        }
    }

    if ($path && is_string($path)) {
        $url .= '/' . ltrim($path, '/');
    }

    /**
     * Filters the URL to the plugins or mu-plugins directory.
     *
     * @param string $url     The complete URL to the plugins directory including scheme and path.
     * @param string $path    Path relative to the URL to the plugins directory. Blank string
     *                        if no path is specified.
     * @param string $plugin  The plugin file path to be relative to. Blank string if no plugin
     *                        is specified.
     */
    return Filter::getInstance()->applyFilter('plugins_url', $url, $path, $plugin);
}

/**
 * Get the URL directory path (with trailing slash) for the plugin __FILE__ passed in.
 *
 * @param string $file The filename of the plugin (__FILE__).
 * @return string the URL path of the directory that contains the plugin.
 * @throws ReflectionException
 * @throws \Qubus\Exception\Exception
 */
function plugin_dir_url(string $file): string
{
    $url = add_trailing_slash(plugin_url('', $file));
    return Filter::getInstance()->applyFilter('plugin_dir_url', $url, $file);
}

/**
 * Retrieves meta data about a plugin.
 *
 * @access private
 * @param string $pluginsDir
 * @return array
 * @throws TypeException
 */
function plugin_info(string $pluginsDir = ''): array
{
    $info = [];
    $dir = glob($pluginsDir . '*/*Plugin.php');
    foreach ($dir as $plugin) {
        $class = PhpFileParser::classObjectFromFile($plugin);
        $info[] = Devflow::inst()::$APP->execute([$class, 'meta']);
    }

    return $info;
}
