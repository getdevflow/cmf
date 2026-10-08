<?php

declare(strict_types=1);

use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Routing\Psr7Router;

return function (Psr7Router $router): void {
    // The legacy API exposed arbitrary tables and interpolated SQL identifiers.
    $router->any('/v1/{path}', static fn () => JsonResponseFactory::create(
        ['error' => 'The table API has been retired. Use the resource endpoints under /v2/.'],
        410
    ))->where(['path' => '.*']);
};
