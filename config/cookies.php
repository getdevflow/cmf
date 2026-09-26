<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\env;

return [
    'path' => env(key: 'COOKIE_PATH', default: '/'),
    'domain' => env(key: 'COOKIE_DOMAIN', default: ''),
    // Authentication envelopes require positive lifetimes (seconds).
    'lifetime' => 86400,
    'remember' => 604800,
    'secure' => env(key: 'COOKIE_SECURE', default: true),
    'samesite' => env(key: 'COOKIE_SAMESITE', default: 'lax'),
    'crypt' => 'sha256',
    'secret_key' => env(key: 'APP_SALT'),
];
