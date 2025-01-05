<?php

declare(strict_types=1);

use Codefy\Framework\Codefy;

use function Codefy\Framework\Helpers\base_path;
use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\resource_path;

return [
    /*
    |--------------------------------------------------------------------------
    | Template paths.
    |--------------------------------------------------------------------------
    | Path of templates. If using Fenom, it must be a string. If using Native
    | it must be an array or if using Foil, you can use a string or array
    | for path.
    */
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

    'options' => [] ,
];
