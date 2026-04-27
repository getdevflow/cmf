<?php

declare(strict_types=1);

use App\Application\Devflow;

use function App\Shared\Helpers\site_url;
use function Codefy\Framework\Helpers\env;
use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\storage_path;

return [
    'enable' => true,

    /*
     |--------------------------------------------------------------------------
     | General settings
     |--------------------------------------------------------------------------
     |
     | General settings for configuring the PageBuilder.
     |
     */
    'general' => [
        'base_url' => site_url(),
        'language' => 'en',
        'assets_url' => '/phpb-assets',
        'uploads_url' => '/uploads'
    ],

    /*
     |--------------------------------------------------------------------------
     | Storage settings
     |--------------------------------------------------------------------------
     |
     | Database and file storage settings.
     |
     */
    'storage' => [
        'use_database' => true,
        'database' => [
            'dsn'    => env(key: 'DB_DSN'),
            'username'  => env('DB_USER'),
            'password'  => env('DB_PASSWORD'),
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => env(key: 'DB_PERSISTENT'),
            ],
            'prefix'    => Devflow::db()->prefix,
        ],
        'uploads_folder' => storage_path('app/vihzhuo/uploads')
    ],

    /*
     |--------------------------------------------------------------------------
     | Auth settings
     |--------------------------------------------------------------------------
     |
     | By default an authentication class is provided which checks for the
     | credentials configured in this setting block.
     |
     */
    'auth' => [
        'use_login' => true,
        'class' => \App\Infrastructure\Services\Vihzhuo\VihzhuoAuth::class,
        'url' => '/admin/logout/',
    ],

    /*
     |--------------------------------------------------------------------------
     | WebsiteManager settings
     |--------------------------------------------------------------------------
     |
     | By default a basic WebsiteManager is provided for creating/editing pages.
     |
     */
    'website_manager' => [
        'use_website_manager' => true,
        'class' => Vihzhuo\Modules\WebsiteManager\WebsiteManager::class,
        'url' => '/admin/manager/'
    ],

    /*
     |--------------------------------------------------------------------------
     | Website settings
     |--------------------------------------------------------------------------
     |
     | By default a setting class is provided for accessing website settings.
     |
     */
    'setting' => [
        'class' => Vihzhuo\Setting::class
    ],

    /*
     |--------------------------------------------------------------------------
     | PageBuilder settings
     |--------------------------------------------------------------------------
     |
     | By default a PageBuilder is provided based on GrapesJS.
     |
     */
    'pagebuilder' => [
        'class' => Vihzhuo\Modules\GrapesJS\PageBuilder::class,
        'url' => '/admin/manager/pagebuilder/',
        'actions' => [
            'back' => '/admin/manager/'
        ]
    ],

    /*
     |--------------------------------------------------------------------------
     | Page settings
     |--------------------------------------------------------------------------
     |
     | By default a Page class is provided with knowledge about its layout and URL.
     |
     */
    'page' => [
        'class' => Vihzhuo\Page::class,
        'table' => 'pages',
        'translation' => [
            'class' => Vihzhuo\PageTranslation::class,
            'table' => 'page_translations',
            'foreign_key' => 'page_id',
        ]
    ],

    /*
     |--------------------------------------------------------------------------
     | Cache settings
     |--------------------------------------------------------------------------
     |
     | Faster load time by skipping block parsing if the page has been requested before.
     | A page will be cached, except if it contains a block with caching set to false.
     | This can be used to prevent caching pages with content that varies per page load.
     | The cached html is removed if the page is saved again in the page builder.
     |
     */
    'cache' => [
        'enabled' => false,
        'folder' => storage_path('framework/cache'),
        'class' => Vihzhuo\Cache::class
    ],

    /*
     |--------------------------------------------------------------------------
     | Theme settings
     |--------------------------------------------------------------------------
     |
     | PageBuilder requires a themes folder in which for each theme the individual
     | theme blocks are defined. A theme block is a sub folder in the themes folder
     | containing a view, model (optional) and controller (optional).
     |
     */
    'theme' => [
        'class' => \App\Infrastructure\Services\Vihzhuo\VihzhuoTheme::class,
        'folder' => public_path('themes'),
        'folder_url' => '/themes',
        'active_theme' => \App\Infrastructure\Services\Vihzhuo\VihzhuoTheme::activeTheme(themeName: 'demo'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Routing settings
     |--------------------------------------------------------------------------
     |
     | Settings for resolving pages based on the current URI.
     |
     */
    'router' => [
        'class' => Vihzhuo\Modules\Router\DatabasePageRouter::class,
        'use_router' => true
    ],

    /*
     |--------------------------------------------------------------------------
     | Class replacements
     |--------------------------------------------------------------------------
     |
     | Allows mapping a class namespace to an alternative namespace,
     | useful for replacing implementations of specific pagebuilder classes.
     | Example: Vihzhuo\UploadedFile::class => Alternative\UploadedFile::class
     | Important: when overriding a class always extend the original class.
     |
     */
    'class_replacements' => [
    ],
];
