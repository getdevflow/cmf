<?php

declare(strict_types=1);

use Codefy\Framework\Proxy\Codefy;

use function Codefy\Framework\Helpers\base_path;
use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\resource_path;

return [
    'path' => [
        'framework' => resource_path(path: 'views'),
        'cms' => resource_path(path: 'views'),
        'cmf' => base_path(path: 'Cms/views'),
        'plugin' => public_path(path: 'plugins'),
        'theme' => public_path(path: 'themes'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cached templates.
    |--------------------------------------------------------------------------
    | Only if supported by the view being used.
    */
    'cache' => resource_path(path: 'views' . Codefy::$PHP::DS . 'cache'),

    /*
    |--------------------------------------------------------------------------
    | View Options.
    |--------------------------------------------------------------------------
    | Only if supported by the view being used.
    */
    'options' => [],
];
