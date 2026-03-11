<?php

declare(strict_types=1);

use Codefy\Framework\Configuration\Middleware;
use Codefy\Framework\Providers\AssetsServiceProvider;
use Codefy\Framework\Providers\LocalizationServiceProvider;
use Codefy\Framework\Support\CodefyServiceProvider;

use function Codefy\Framework\Helpers\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => env(key: 'APP_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env(key: 'APP_ENV'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => env(key: 'APP_DEBUG'),

    /*
    |--------------------------------------------------------------------------
    | Application Base Path
    |--------------------------------------------------------------------------
    */
    'path' => env(key: 'APP_BASE_PATH'),
    /*
    |--------------------------------------------------------------------------
    | Application Base Url
    |--------------------------------------------------------------------------
    */
    'url' => env(key: 'APP_URL', default: 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => 'America/Los_Angeles',

    /*
    |--------------------------------------------------------------------------
    | Application Locale
    |--------------------------------------------------------------------------
    */
    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application HTML Charset
    |--------------------------------------------------------------------------
    */
    'charset' => 'UTF-8',

    /*
    |--------------------------------------------------------------------------
    | Application HTML Language
    |--------------------------------------------------------------------------
    */
    'language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Domain
    |--------------------------------------------------------------------------
    */
    'locale_domain' => 'devflow',

    /*
    |--------------------------------------------------------------------------
    | API key for restful routes.
    |--------------------------------------------------------------------------
    */
    'api_key' => env(key: 'APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */
    'crypto_key' => file_get_contents(filename: __DIR__ . '/../.enc.key'),

    /*
    |--------------------------------------------------------------------------
    | Event Listener Provider and Dispatcher
    |--------------------------------------------------------------------------
    */
    'event_listener' => Qubus\EventDispatcher\Providers\PrioritizedProvider::class,
    'event_dispatcher' => Qubus\EventDispatcher\EventDispatcher::class,

    /*
    |--------------------------------------------------------------------------
    | Controller Namespace
    |--------------------------------------------------------------------------
    */
    //'controller_namespace' => 'Application\\Http\\Controller',

    /*
    |--------------------------------------------------------------------------
    | Application Configured Service Providers
    |--------------------------------------------------------------------------
    | These service providers will automatically load when the application is
    | requested. Feel free to add your own service providers.
    */
    'providers' => CodefyServiceProvider::defaultProviders()->merge([
        //
    ])
    ->except([LocalizationServiceProvider::class, AssetsServiceProvider::class])
    ->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    | Middleware aliases are registered here, but to use a middleware, you
    | can add them to a route, a group of routes or controllers.
    */
    'middlewares' => Middleware::defaultMiddlewares()->merge([
        'rest.api' => \Application\Http\Middleware\RestApiMiddleware::class,
        'fence' => App\Infrastructure\Http\Middleware\UserAuthMiddleware::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Base Middlewares
    |--------------------------------------------------------------------------
    | Register middleware class strings or aliases to be applied to the entire
    | application.
    */
    'base_middlewares' => [
        'security.headers',
        'csrf.token',
        'csrf.protection',
        'http.cache.prevention',
        'user.cookie.decrypt',
        'bind.request',
        'php.debugbar',
        //'http.exception', //uncomment in production
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Console Commands
    |--------------------------------------------------------------------------
    | These console commands will automatically load when the application is
    | requested. Feel free to add your own console commands.
    */
    'commands' => [
        /*
         * Application Console Commands . . .
         */
        App\Application\Console\Commands\ClearCacheCommand::class,
        App\Application\Console\Commands\GenerateEncryptionKeyCommand::class,
        App\Application\Console\Commands\GenerateSaltStringCommand::class,
        App\Application\Console\Commands\InstallCmsCommand::class,
        App\Application\Console\Commands\EmailSendCommand::class,
        App\Application\Console\Commands\UpdaterCheckCommand::class,
        App\Application\Console\Commands\UpdaterCommand::class,
    ]
];
