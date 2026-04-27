<?php

return function (\Qubus\Routing\Psr7Router $router) {
    $router->group(['middleware' => 'rest.api', 'prefix' => '/v2'], function ($group) {
        $group->get(uri: '/content/', callback: 'ContentRestController@index')
            ->name('v2.content.index');
        $group->get(uri: '/content/{id}/', callback: 'ContentRestController@show')
                ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.content.show');
        $group->post(uri: '/content/store/', callback: 'ContentRestController@store');
        $group->map(verbs: ['PUT', 'PATCH'], uri: '/content/{id}/', callback: 'ContentRestController@update')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.content.update');
        $group->delete(uri: '/content/{id}/', callback: 'ContentRestController@destroy')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.content.destroy');

        $group->get(uri: '/user/', callback: 'UserRestController@index')
            ->name('v2.user.index');
        $group->get(uri: '/user/{id}/', callback: 'UserRestController@show')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.user.show');
        $group->post(uri: '/user/store/', callback: 'UserRestController@store');
        $group->map(verbs: ['PUT', 'PATCH'], uri: '/user/{id}/', callback: 'UserRestController@update')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.user.update');
        $group->delete(uri: '/user/{id}/', callback: 'UserRestController@remove')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.user.remove');

        $group->get(uri: '/product/', callback: 'ProductRestController@index')
            ->name('v2.product.index');
        $group->get(uri: '/product/{id}/', callback: 'ProductRestController@show')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.product.show');
        $group->post(uri: '/product/store/', callback: 'ProductRestController@store');
        $group->map(verbs: ['PUT', 'PATCH'], uri: '/product/{id}/', callback: 'ProductRestController@update')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.product.update');
        $group->delete(uri: '/product/{id}/', callback: 'ProductRestController@destroy')
            ->where(['id' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
            ->name('v2.product.destroy');
    });
};
