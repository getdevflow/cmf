<?php

return function (\Qubus\Routing\Psr7Router $router) {
    $router->get(uri: '/v1/{table}/', callback: 'ApiController@all')->middleware('rest.api');
    $router->get(uri: '/v1/{table}/{field}/{value}', callback: 'ApiController@column')->middleware('rest.api');
};
