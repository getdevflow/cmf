<?php

use function Qubus\Config\Helpers\env;
use function Qubus\Security\Helpers\t__;

return [
    /*
    |--------------------------------------------------------------------------
    | User session cookie name.
    |--------------------------------------------------------------------------
    */
    'cookie_name' => 'USERSESSID',

    /**
     * Do not use the default app encryption key found in .env.example.
     * Generate a new encryption key by running this console command:
     * php codex generate:key
     */
    'encryption_key' => env(key: 'APP_ENCRYPTION_KEY'),

    'login_route' => 'login',

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

    'redirect_guests_to' => '/admin/login/',

    'password_min_length' => 26,

    'username_min_length' => 6,

    'access_denied_message' => t__(msgid: 'Access denied.', domain: 'devflow'),
];
