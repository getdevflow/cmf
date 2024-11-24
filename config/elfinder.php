<?php

use function Codefy\Framework\Helpers\public_path;

return [
    /*
    |--------------------------------------------------------------------------
    | Upload dir
    |--------------------------------------------------------------------------
    |
    | The dir where to store the images (relative from site path)
    |
    */
    'dir' => ['uploads/'],

    /*
    |--------------------------------------------------------------------------
    | Filesystem disks (Flysystem)
    |--------------------------------------------------------------------------
    |
    | Define an array of Filesystem disks, which use Flysystem.
    | You can set extra options, example:
    |
    | 'my-disk' => [
    |        'URL' => url('to/disk'),
    |        'alias' => 'Local storage',
    |    ]
    */
    'disks' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Access filter
    |--------------------------------------------------------------------------
    |
    | Filter callback to check the files
    |
    */
    'access' => 'App\Shared\Helpers\Elfinder\Elfinder::checkAccess',

    /*
    |--------------------------------------------------------------------------
    | Roots
    |--------------------------------------------------------------------------
    |
    | By default, the roots file is LocalFileSystem, with the above public dir.
    | If you want custom options, you can set your own roots below.
    |
    */
    'roots' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, together with 'roots' and passed to the Connector.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1
    |
    */
    'options' => [
        'debug' => false,
        'locale' => 'en_US.UTF-8',
        'uploadTempPath' => public_path('sites/tmp/'),
        'commonTempPath' => public_path('sites/tmp/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Root Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, together with every root by default.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1#root-options
    |
    */
    'root_options' => [
        'uploadMaxSize' => '500M',
        'uploadDeny'    => ['all'],
        'uploadAllow' => [
            'text/plain', 'image/png', 'image/jpeg', 'image/gif', 'application/zip',
            'text/csv', 'application/pdf', 'application/msword', 'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint', 'application/msword', 'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint', 'video/mp4'
        ],
        'uploadOrder' => ['deny', 'allow'],
    ],
];
