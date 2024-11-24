<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\storage_path;
use function Qubus\Config\Helpers\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Filesystem disks. You may add as many filesystem disks as you need.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |--------------------------------------------------------------------------
    */
    'disks' => [
        /*
        |--------------------------------------------------------------------------
        | Default local disk.
        |--------------------------------------------------------------------------
        */
        'local' => [
            'root' => storage_path(),
            'visibility' => \League\Flysystem\Visibility::PUBLIC,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Public disk.
        |--------------------------------------------------------------------------
        */
        'public' => [
            'root' => storage_path(path: 'app/public'),
            'visibility' => \League\Flysystem\Visibility::PUBLIC,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache disk.
        |--------------------------------------------------------------------------
        */
        'cache' => [
            'root' => storage_path(path: 'framework/cache'),
            'visibility' => \League\Flysystem\Visibility::PRIVATE,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Media disk.
        |--------------------------------------------------------------------------
        */
        'media' => [
            'root' => storage_path(path: 'framework/media'),
            'visibility' => \League\Flysystem\Visibility::PUBLIC,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Session disk.
        |--------------------------------------------------------------------------
        */
        'sessions' => [
            'root' => storage_path(path: 'framework/sessions'),
            'visibility' => \League\Flysystem\Visibility::PRIVATE,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Session disk.
        |--------------------------------------------------------------------------
        */
        'cookies' => [
            'root' => storage_path(path: 'framework/cookies'),
            'visibility' => \League\Flysystem\Visibility::PRIVATE,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | View disk.
        |--------------------------------------------------------------------------
        */
        'views' => [
            'root' => storage_path(path: 'framework/views'),
            'visibility' => \League\Flysystem\Visibility::PRIVATE,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Log disk.
        |--------------------------------------------------------------------------
        */
        'logs' => [
            'root' => storage_path(path: 'logs'),
            'visibility' => \League\Flysystem\Visibility::PRIVATE,
            'permission' => [
                'file' => [
                    'public'  => 0644,
                    'private' => 0604,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 7604,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Amazon S3
        |--------------------------------------------------------------------------
        */
        'awsS3' => [
            'driver' => 's3',
            'key' => env(key: 'AWS_ACCESS_KEY_ID'),
            'secret' => env(key: 'AWS_SECRET_ACCESS_KEY'),
            'region' => env(key: 'AWS_DEFAULT_REGION'),
            'bucket' => env(key: 'AWS_BUCKET'),
            'url' => env(key: 'AWS_URL'),
            'endpoint' => env(key: 'AWS_ENDPOINT'),
            'prefix' => '',
            'visibility' => \League\Flysystem\Visibility::PRIVATE,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Config for Local FileSystem.
    |--------------------------------------------------------------------------
    */
    'local' => [
        /*
        |--------------------------------------------------------------------------
        | Set the root directory.
        |--------------------------------------------------------------------------
        */
        'root' => storage_path(),
        /*
        |--------------------------------------------------------------------------
        | Set the visibility for files and directories.
        |--------------------------------------------------------------------------
        */
        'visibility' => \League\Flysystem\Visibility::PUBLIC,
        'permission' => [
            'file' => [
                'public'  => 0644,
                'private' => 0604,
            ],
            'dir'  => [
                'public'  => 0755,
                'private' => 7604,
            ],
        ],
    ],
];
