<?php

return [
    Application\Provider\CacheAdapterServiceProvider::class,
    App\Infrastructure\Providers\LocaleServiceProvider::class,
    App\Infrastructure\Providers\AppServiceProvider::class,
    Application\Provider\SchedulerServiceProvider::class,
    App\Infrastructure\Providers\MiddlewareServiceProvider::class,
    App\Infrastructure\Providers\CmsHelperServiceProvider::class,
    App\Infrastructure\Providers\DebugBarServiceProvider::class,
    Application\Provider\VihzhuoBlocksServiceProvider::class,
    Application\Provider\FirewallServiceProvider::class,
];
