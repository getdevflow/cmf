<?php

declare(strict_types=1);

use App\Shared\Http\RequestMethod;

/*
|--------------------------------------------------------------------------
| Define your Request headers.
|--------------------------------------------------------------------------
*/
return [
    'access-control-allow-methods' => [
        RequestMethod::POST,
        RequestMethod::GET,
        RequestMethod::OPTIONS,
        RequestMethod::PUT,
        RequestMethod::DELETE
    ],

    'access-control-allow-origin' => ['*'],

    'access-control-allow-headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Requested-With',
        'Origin',
        'X-CSRF-Token'
    ],

    'access-control-allow-credentials' => ['false'],

    'access-control-expose-headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma'
    ],

    'access-control-max-age' => [3600],
];
