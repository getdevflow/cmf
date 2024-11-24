<?php

use function Codefy\Framework\Helpers\env;

return [
    /**
     * Do not use the default app encryption key found in .env.example.
     * Generate a new encryption key by running this console command:
     * php codex generate:key
     */
    'encryption_key' => env(key: 'APP_ENCRYPTION_KEY'),

    'login_route' => '/login/',

    'login_url' => env(key: 'APP_BASE_URL') . '/admin/login/',

    'admin_url' => env(key: 'APP_BASE_URL') . '/admin/',

    'pdo' => [
        /** name of the user's table */
        'table' => env(key: 'DB_TABLE_PREFIX') . 'user',

        'fields' => [
            /** field name to use for identity (email, username, token) */
            'identity' => 'user_login',
            /** name of the role field */
            'role' => 'role',
            /** name of the token field */
            'token' => 'user_token',
            /** name of the password field */
            'password' => 'user_pass',
        ],

    ],

    /** Where should users be redirected when authentication fails? */
    'http_redirect' => '',
];
