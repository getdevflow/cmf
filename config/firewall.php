<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'block' => true,

    'log_payload' => false,

    'alert_min_severity' => 'high',

    'notifiers' => [],

    'ignored_paths' => [
        '/favicon.ico',
        '/robots.txt',
    ],

    'sql_injection' => [],

    'xss' => [],

    // Remote Code Execution
    'rce' => [],

    'file_traversal' => [],

    // Server-Side Request Forgery
    'ssrf' => [],

    'scanner_path_probe' => [],

    'sensitive_file_probe' => [],

    'php_probe' => [],
];
