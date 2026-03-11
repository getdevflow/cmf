<?php

declare(strict_types=1);

use function Qubus\Config\Helpers\env;

return [
    'path' => '/',
    'domain' => '',
    'lifetime' => (int) 86400,
    'remember' => (int) 604800,
    'secure' => false,
    'samesite' => 'lax',
    'crypt' => 'sha256',
    'secret_key' => env(key: 'APP_SALT'),
];
