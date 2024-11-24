<?php

declare(strict_types=1);

return [
    'use_cookies'            => 1,
    'cookie_secure'          => 1,
    'cookie_lifetime'        => 3600,
    'cookie_path'            => '/',
    'cookie_domain'          => '',
    'use_only_cookies'       => 1,
    'cookie_httponly'        => 1,
    'use_strict_mode'        => 1,
    'sid_bits_per_character' => 5,
    'sid_length'             => 48,
    'cache_limiter'          => 'nocache',
    'cache_expire'           => 1800,
    'cookie_samesite'        => 'Lax',
];
