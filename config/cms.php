<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\public_path;
use function Qubus\Config\Helpers\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin's base directory.
    |--------------------------------------------------------------------------
    */
    'encryption_key' => env(key: 'APP_ENCRYPTION_KEY'),
    /*
    |--------------------------------------------------------------------------
    | Plugin's base directory.
    |--------------------------------------------------------------------------
    */
    'plugin_dir' => public_path(path: 'plugins/'),
    /*
    |--------------------------------------------------------------------------
    | Theme's base directory.
    |--------------------------------------------------------------------------
    */
    'theme_dir' => public_path(path: 'themes/'),
    /*
    |--------------------------------------------------------------------------
    | Site's base directory.
    |--------------------------------------------------------------------------
    */
    'site_dir' => public_path(path: 'sites/'),
    /*
    |--------------------------------------------------------------------------
    | If you don't want the content urls to be prepended with the
    | content type slug, then change this to an empty string.
    | (i.e. movie/content-slug/ vs. content-slug/)
    |--------------------------------------------------------------------------
    */
    'relative_url' => 'contenttype',
    /*
    |--------------------------------------------------------------------------
    | Default system locale.
    |--------------------------------------------------------------------------
    */
    'core_local' => 'en',
    /*
    |--------------------------------------------------------------------------
    | Email encoding filter priority.
    |--------------------------------------------------------------------------
    */
    'eae_filter_priority' => 1000,
    /*
    |--------------------------------------------------------------------------
    | Main site url.
    |--------------------------------------------------------------------------
    */
    'main_site_url' => 'localhost:8080',
    /*
    |--------------------------------------------------------------------------
    | Main site path.
    |--------------------------------------------------------------------------
    */
    'main_site_path' => '/',
    /*
    |--------------------------------------------------------------------------
    | Multisite: enable or disable. Experimental: set `true` at your
    | own risk.
    |--------------------------------------------------------------------------
    */
    'multisite' => false,
    /*
    |--------------------------------------------------------------------------
    | Set the minimum character length for passwords.
    |--------------------------------------------------------------------------
    */
    'password_length' => 26,
];
