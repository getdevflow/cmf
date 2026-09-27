<?php

declare(strict_types=1);

use Codefy\Framework\Application as DevflowApp;
use Qubus\Http\Response;
use Qubus\Routing\Router;

use function Codefy\Framework\Helpers\env;

$app = DevflowApp::create(
    config: [
        'basePath' => env(key: 'APP_BASE_PATH', default: dirname(path: __DIR__))
    ]
)
->withProviders([
    App\Infrastructure\Providers\SiteServiceProvider::class,
    App\Infrastructure\Providers\DatabaseServiceProvider::class,
    App\Infrastructure\Providers\OptionsServiceProvider::class,
    App\Infrastructure\Providers\RbacServiceProvider::class,
    \Application\Provider\EventListenerServiceProvider::class,
    \Application\Provider\ViewServiceProvider::class,
])
->withSingletons([
    //
])
->withRouting(
    web: [
        dirname(path: __DIR__) . '/routes/web/admin.php',
        dirname(path: __DIR__) . '/routes/api/v1.php',
        dirname(path: __DIR__) . '/routes/api/v2.php',
        dirname(path: __DIR__) . '/routes/web/web.php',
    ],
    then: static function (Router $router): void {
        // Let CORS evaluate preflights even when the target only declares POST/PUT/etc.
        // Explicit OPTIONS routes registered above take precedence over this fallback.
        $router->map(['OPTIONS'], '*', static fn(): Response => new Response(status: 404));
    },
)
->return();

$app->share(nameOrInstance: $app);

return $app::getInstance();
