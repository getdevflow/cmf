<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\env;

return [
    'enabled' => true,
    'ttl' => 1800,
    'namespace' => 'simple',

    'redis' => [
        'host' => env(key: 'REDIS_HOST', default: '127.0.0.1'),
        'port' => env(key: 'REDIS_PORT'),
        'persistent' => env(key: 'REDIS_PERSISTENT'),
    ],
];
