<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Infrastructure\Services\Options;
use Codefy\Framework\Codefy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Exception;
use ReflectionException;

use function Codefy\Framework\Helpers\config;
use function file_get_contents;
use function Qubus\Security\Helpers\esc_url;
use function Qubus\Security\Helpers\load_textdomain;

/**
 * Retrieves a list of available locales.
 *
 * @file App/Shared/Helpers/domain.php
 * @param string $active
 */
function cms_dropdown_languages(string $active = ''): void
{
    $protocol = is_ssl() ? 'https://' : 'http://';

    $locales = file_get_contents(esc_url($protocol . 'tritan-cms.s3.amazonaws.com/api/1.1/locale.json'));
    $json = json_decode($locales, true);
    foreach ($json as $locale) {
        echo '<option value="' . $locale['language'] . '"' . selected($active, $locale['language'], false) . '>'
        . $locale['native_name'] . '</option>';
    }
}

/**
 * Load a plugin's translated strings.
 *
 * If the path is not given then it will be the root of the plugin directory.
 *
 * @file App/Shared/Helpers/domain.php
 * @param string $domain Unique identifier for retrieving translated strings
 * @param false|string $pluginRelPath Optional. Relative path to plugin dir where the locale directory resides.
 *                                    Default false.
 * @return bool True when textdomain is successfully loaded, false otherwise.
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws ReflectionException
 */
function load_plugin_textdomain(string $domain, false|string $pluginRelPath = false): bool
{
    $locale = load_core_locale();
    /**
     * Filter a plugin's locale.
     *
     * @file App/Shared/Helpers/domain.php
     * @param string $locale The plugin's current locale.
     * @param string $domain Text domain. Unique identifier for retrieving translated strings.
     */
    $pluginLocale = Filter::getInstance()->applyFilter('plugin_locale', $locale, $domain);

    if ($pluginRelPath !== false) {
        $path = config('system.cms_plugin_dir') . $pluginRelPath . Codefy::$PHP::DS;
    } else {
        $path = config('system.cms_plugin_dir');
    }

    $mofile = $path . $domain . '-' . $pluginLocale . '.mo';
    if (load_textdomain($domain, $mofile)) {
        return true;
    }

    return false;
}

/**
 * Loads the current or default locale.
 *
 * @file App/Shared/Helpers/domain.php
 * @return string The locale.
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws ReflectionException
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function load_core_locale(): string
{
    $option = Options::factory();
    $locale = $option->read(optionKey: 'cms_core_locale', default: config(key: 'app.locale'));
    return Filter::getInstance()->applyFilter('core_locale', $locale);
}
