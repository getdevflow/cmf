<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Database;
use Codefy\Framework\Factory\FileLoggerFactory;
use Exception;
use PDOException;
use Qubus\EventDispatcher\ActionFilter\Action;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function App\Shared\Helpers\normalize_path;
use function Codefy\Framework\Helpers\public_path;
use function Qubus\Support\Helpers\add_trailing_slash;
use function sprintf;
use function trim;

final class Plugin
{
    protected static ?Database $cdb = null;

    public function __construct()
    {
        self::$cdb = dfdb();
    }

    /**
     * Extracts the file name of a specific plugin.
     *
     * @param string $filename Plugin's file name.
     */
    public static function basename(string $filename): string
    {
        $pluginDir = normalize_path(public_path('plugins'));

        $filename = trim($pluginDir, '/');
        return \basename($filename);
    }

    /**
     * When a plugin is activated, the action `activate_name` hook is called.
     * `name` is replaced by the actual file name of the plugin being activated.
     * So if the plugin is located at 'storage/app/plugins/sample.plugin.php', then the hook will
     * call 'activate_sample.plugin.php'.
     *
     * @param string $filename Plugin's filename.
     * @param mixed $function The function that should be triggered by the hook.
     * @throws ReflectionException
     */
    public static function registerActivationHook(string $filename, mixed $function): void
    {
        $filename = self::basename($filename);
        Action::getInstance()->addAction(hook: "activate_{$filename}", callback: $function);
    }

    /**
     * When a plugin is deactivated, the action `deactivate_name` hook is called.
     * `name` is replaced by the actual file name of the plugin being deactivated.
     * So if the plugin is located at 'storage/app/plugins/sample.plugin.php', then the hook will
     * call 'deactivate_sample.plugin.php'.
     *
     * @param string $filename Plugin's filename.
     * @param mixed $function The function that should be triggered by the hook.
     * @throws ReflectionException
     */
    public static function registerDeactivationHook(string $filename, mixed $function): void
    {
        $filename = self::basename($filename);
        Action::getInstance()->addAction(hook: "deactivate_{$filename}", callback: $function);
    }

    /**
     * Get the filesystem directory path (with trailing slash) for the plugin __FILE__ passed in.
     *
     * @param string $filename The filename of the plugin (__FILE__).
     * @return string The filesystem path of the directory that contains the plugin.
     */
    public static function dirPath(string $filename): string
    {
        return add_trailing_slash($filename);
    }

    /**
     * Returns a list of all plugins that have been activated.
     *
     * @return array
     */
    public static function active(): array
    {
        $cdb = self::$cdb;

        $sql = $cdb->raw(sql: "SELECT * FROM {$cdb->prefix}plugin");
        return $sql->fetchAll();
    }

    /**
     * Activates a specific plugin that is called by $_GET['id'] variable.
     *
     * @param string $plugin ID of the plugin to activate
     * @throws ReflectionException
     * @throws Exception
     */
    public static function activate(string $plugin): void
    {
        $cdb = self::$cdb;

        try {
            $cdb->qb()->transactional(function ($db) use ($plugin) {
                $db->qb()->table(tableName: "{$db->prefix}plugin")->insert(data: ['plugin_location' => $plugin]);
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
     * @throws ReflectionException
     */
    public static function deactivate(string $plugin): void
    {
        $cdb = self::$cdb;

        try {
            $cdb->qb()->transactional(function ($db) use ($plugin) {
                $db->qb()
                    ->table(tableName: "{$db->prefix}plugin")
                    ->where(condition: 'plugin_location = ?', parameters: $plugin)
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
     * @throws ReflectionException
     * @throws \Qubus\Exception\Exception
     */
    public static function isActivated(string $plugin): bool
    {
        $cdb = self::$cdb;

        $prepare = $cdb->prepare(
            query: "SELECT COUNT(*) FROM {$cdb->prefix}plugin WHERE plugin_location = ?",
            params: [
                    $plugin
            ]
        );
        $count = $cdb->getVar($prepare);

        return $count > 0;
    }
}
