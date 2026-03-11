<?php

return [
    \Application\Provider\CacheAdapterServiceProvider::class,
    App\Infrastructure\Providers\TranslatorServiceProvider::class,
    App\Infrastructure\Providers\AppServiceProvider::class,
    App\Infrastructure\Providers\SiteServiceProvider::class,
    \Application\Provider\SchedulerServiceProvider::class,
    App\Infrastructure\Providers\MiddlewareServiceProvider::class,
    App\Infrastructure\Providers\CmsHelperServiceProvider::class,
    App\Infrastructure\Providers\DebugBarServiceProvider::class,
    //App\Infrastructure\Providers\VihzhuoBlocksServiceProvider::class,
];
