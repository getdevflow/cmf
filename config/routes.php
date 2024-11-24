<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Define your different class routes in Cms/Http/Routes.
|--------------------------------------------------------------------------
*/

use Cms\Http\Routes\TestRoute;

return [
    'localhost' => TestRoute::class,
];
