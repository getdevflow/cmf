<?php

declare(strict_types=1);

namespace Cms\Http\Routes;

use Qubus\Routing\Router;

class TestRoute
{
    public function handle(Router $router): void
    {
        $router->get('/custom-route/test', function () {
            return 'test';
        });
    }
}
