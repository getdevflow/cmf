<?php

declare(strict_types=1);

namespace Application\Http\Route;

use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Psr7Router;

class TestRoute
{
    /**
     * @throws TooLateToAddNewRouteException
     */
    public function handle(Psr7Router $router): void
    {
        $router->get('/admin/custom-route/test', function () {
            return 'Hello World!';
        });
    }
}
