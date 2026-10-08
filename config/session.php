<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\env;

return [
    'use_cookies'            => 1,
    'cookie_secure'          => (int) env(key: 'COOKIE_SECURE', default: true),
    'cookie_lifetime'        => 2592000,
    'cookie_path'            => env(key: 'COOKIE_PATH', default: '/'),
    'cookie_domain'          => env(key: 'COOKIE_DOMAIN', default: ''),
    'use_only_cookies'       => 1,
    'cookie_httponly'        => 1,
    'use_strict_mode'        => 1,
    'cache_limiter'          => 'nocache',
    'cache_expire'           => 1800,
    'cookie_samesite'        => env(key: 'COOKIE_SAMESITE', default: 'lax'),
    'name'                   => 'DEVFLOWSESSID',
];
