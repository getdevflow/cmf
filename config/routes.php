<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Define your different class routes in Cms/Http/Routes.
|--------------------------------------------------------------------------
*/

use Application\Http\Route\TestRoute;

return [
    'localhost' => TestRoute::class,
];
