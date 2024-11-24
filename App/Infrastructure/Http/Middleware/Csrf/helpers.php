<?php

declare(strict_types=1);

use App\Infrastructure\Http\Middleware\Csrf\CsrfTokenMiddleware;

if (!function_exists(function: 'csrf_field')) {
    /**
     * @throws \Qubus\Exception\Exception
     */
    function csrf_field(): string
    {
        return CsrfTokenMiddleware::getField();
    }
}
