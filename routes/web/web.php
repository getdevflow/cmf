<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\config;
use function Qubus\Security\Helpers\__observer;

return function (\Qubus\Routing\Psr7Router $router) {
    // Custom plugin routes
    __observer()->filter->applyFilter('plugin.route', $router);
    // Custom theme routes
    __observer()->filter->applyFilter('theme.route', $router);

    if (config()->boolean(key: 'vihzhuo.enable')) {
        $router
            ->get(
                uri: config()->string(key: 'vihzhuo.general.assets_url') . '{any}',
                callback: 'PageBuilderController@assets'
            )
            ->where(['any' => '.*']);

        $router
            ->get(
                uri: config()->string(key: 'vihzhuo.general.uploads_url') . '{any}',
                callback: 'PageBuilderController@uploads'
            )
            ->where(['any' => '.*']);

        if (config()->boolean(key: 'vihzhuo.website_manager.use_website_manager')) {
            $router
                ->map(
                    ['GET', 'POST'],
                    uri: config()->string(key: 'vihzhuo.website_manager.url') . '{any}',
                    callback: 'PageBuilderController@websiteManager'
                )
                ->where(['any' => '.*']);
        }

        if (config()->boolean(key: 'vihzhuo.router.use_router')) {
            $router
                ->get(
                    uri: '/{any}',
                    callback: 'PageBuilderController@any'
                )
                ->where(['any' => '.*']);
        }
    }
};
