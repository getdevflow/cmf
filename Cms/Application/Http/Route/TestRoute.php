<?php

declare(strict_types=1);

namespace Application\Http\Route;

use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Router;

class TestRoute
{
    /**
     * @throws TooLateToAddNewRouteException
     */
    public function handle(Router $router): void
    {
        $router->get('/custom-route/test', function () {
            return 'test';
        });
    }
}
