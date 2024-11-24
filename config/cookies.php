<?php

declare(strict_types=1);

return [
    'path' => '/',
    'domain' => 'localhost',
    'lifetime' => (int) 86400,
    'secure' => false,
    'samesite' => 'lax',
    'crypt' => 'sha256',
    'secret_key' => 'please change this to a secure salt',
];
