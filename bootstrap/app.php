<?php

declare(strict_types=1);

use Codefy\Framework\Application as DevflowApp;
use Qubus\Exception\Data\TypeException;

use function Codefy\Framework\Helpers\env;

try {
    $app = DevflowApp::create(
        config: [
            'basePath' => env(key: 'APP_BASE_PATH', default: dirname(path: __DIR__))
        ]
    )
    //->withEncryptedEnv(bool: true)
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
    )
    ->return();

    $app->share(nameOrInstance: $app);

    return $app::getInstance();
} catch (TypeException|ReflectionException $e) {
    return $e->getMessage();
}
