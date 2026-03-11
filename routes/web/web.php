<?php

declare(strict_types=1);

use Psr\Http\Message\RequestInterface;
use Qubus\Http\Request;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;
use function Qubus\Security\Helpers\__observer;

return function (\Qubus\Routing\Psr7Router $router) {
    /** @var Request $request */
    $request = app(RequestInterface::class);

    // Custom plugin routes
    __observer()->filter->applyFilter('plugin.route', $router);
    // Custom theme routes
    __observer()->filter->applyFilter('theme.route', $router);

    /*
     * Set the default controller namespace for custom Devflow development.
     */
    $router->setDefaultNamespace(namespace: '\\Application\\Http\\Controller');
    $router->get(uri: '/cron/', callback: 'CronController@cron');
    $cmsRoutes = config()->array(key: 'routes');

    if (!empty($cmsRoutes)) {
        foreach ($cmsRoutes as $host => $route) {
            if ($host === $request->getUri()->getHost()) {
                app()->execute(callableOrMethodStr: [$route, 'handle']);
            }
        }
    }
};
