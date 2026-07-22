<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'block' => true,

    'log_payload' => false,

    'alert_min_severity' => 'high',

    'notifiers' => [],

    /*
     * Completely bypass every firewall rule for these paths.
     *
     * Use this sparingly. Rule-specific exclusions below are safer.
     */
    'ignored_paths' => [
        '/favicon.ico',
        '/robots.txt',
    ],

    /*
     * Targeted exclusions.
     *
     * Empty methods, rules, sources, or fields mean "all" for that
     * dimension. Use "*" explicitly when it improves readability.
     */
    'exclusions' => [],

    'rules' => [
        'sql_injection' => [
            'enabled' => true,

            // Additional regular expressions.
            'add' => [],

            // Exact built-in regex strings to remove.
            'remove' => [],

            // When non-empty, replaces all built-in patterns.
            'replace' => [],
        ],

        'xss' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'rce' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'file_traversal' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'ssrf' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'scanner_path_probe' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'sensitive_file_probe' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'wordpress_probe' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],

        'php_probe' => [
            'enabled' => true,
            'add' => [],
            'remove' => [],
            'replace' => [],
        ],
    ],

    /*
     * Deprecated legacy additions.
     *
     * Keep temporarily so existing applications do not break. New
     * configuration should use rules.<type>.add above.
     */
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
