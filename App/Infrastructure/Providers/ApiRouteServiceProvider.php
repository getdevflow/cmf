<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Router;

final class ApiRouteServiceProvider extends CodefyServiceProvider
{
    /**
     * @return void
     * @throws TooLateToAddNewRouteException
     */
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        /** @var $router Router */
        $router = $this->codefy->make(name: 'router');

        $router->get(uri: '/v1/{table}/', callback: 'ApiController@all')->middleware('rest.api');
        $router->get(uri: '/v1/{table}/{field}/{value}', callback: 'ApiController@column')->middleware('rest.api');
    }
}
