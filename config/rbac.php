<?php

return [
    /** Named or grouped permissions. */
    'permissions' => [
        'admin' => [
            'description' => 'All system permissions',
            'permissions' => [
                'access:admin' => ['description' => 'Access to the dashboard.'],
                'create:content' => ['description' => 'Create content.'],
                'create:product' => ['description' => 'Create product.'],
                'create:users' => ['description' => 'Create users.'],
                'manage:content' => ['description' => 'Manage content.'],
                'manage:product' => ['description' => 'Manage product.'],
                'manage:users' => ['description' => 'Manage users.'],
                'manage:media' => ['description' => 'Manage media.'],
                'manage:options' => ['description' => 'Manage options.'],
                'manage:settings' => ['description' => 'Manage settings.'],
                'manage:products' => ['description' => 'Manage product inventory.'],
                'manage:plugins' => ['description' => 'Manage plugins.'],
                'manage:themes' => ['description' => 'Manage themes.'],
                'update:content' => ['description' => 'Update content.'],
                'update:product' => ['description' => 'Update product.'],
                'update:users' => ['description' => 'Update users.'],
                'delete:content' => ['description' => 'Delete content.'],
                'delete:product' => ['description' => 'Delete product.'],
                'delete:users' => ['description' => 'Delete users.'],
                'switch:user' => ['description' => 'Switch user'],
                'publish:content' => ['description' => 'Publish content.'],
                'publish:product' => ['description' => 'Publish product.'],
                'activate:plugins' => ['description' => 'Activate plugins.'],
                'deactivate:plugins' => ['description' => 'Deactivate plugins.'],
            ],
        ],
        'sites' => [
            'description' => 'All site permissions',
            'permissions' => [
                'manage:sites' => ['description' => 'Manage sites.'],
                'create:sites' => ['description' => 'Create sites.'],
                'update:sites' => ['description' => 'Update sites.'],
                'delete:sites' => ['description' => 'Delete sites.'],
            ]
        ],
    ],

    'roles' => [
        'super' => [
            'description' => 'Super administrator',
            'permissions' => ['admin','sites'],
        ],
        'admin' => [
            'description' => 'Administrator',
            'permissions' => [
                'access:admin',
                'create:content',
                'manage:content',
                'update:content',
                'delete:content',
                'publish:content',
                'create:product',
                'manage:product',
                'update:product',
                'delete:product',
                'publish:product',
                'manage:media',
                'manage:options',
                'manage:settings',
                'manage:plugins',
                'manage:themes',
                'activate:plugins',
                'deactivate:plugins',
            ],
        ],
        'editor' => [
            'description' => 'Site editor',
            'permissions' => [
                'access:admin',
                'create:content',
                'manage:content',
                'update:content',
                'delete:content',
                'publish:content',
                'create:product',
                'manage:product',
                'update:product',
                'delete:product',
                'publish:product',
                'manage:media',
            ],
        ],
    ],
];
