<?php

declare(strict_types=1);

use Psr\Http\Message\RequestInterface;
use Qubus\Http\Request;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;
use function Qubus\Security\Helpers\__observer;

return function (\Qubus\Routing\Psr7Router $router) {
    $loginRoute = config()->string(key: 'auth.login_route');

    $router->group(
            params: ['prefix' => '/admin'],
            callback: function ($group) use ($loginRoute) {
                $group->get(uri: '/', callback: 'AdminDashboardController@index')
                        ->name('admin.dashboard');

                $group->get(uri: '/snapshot/', callback: 'AdminDashboardController@snapshot')
                        ->name('admin.snapshot');

                $group->post(uri: '/auth/', callback: 'AdminAuthController@auth')
                        ->name('admin.auth')
                        ->middleware(['user.authenticate','user.session']);

                $group->get(uri: '/flush-cache/', callback: 'AdminDashboardController@flushCache')
                        ->name('admin.cache.flush');

                $group->map(['GET', 'POST'], '/connector/', callback: 'AdminMediaController@connector')
                        ->name('admin.connector');

                $group->get(uri: '/elfinder/', callback: 'AdminMediaController@elFinder')
                        ->name('admin.elfinder');

                $group->get(uri: '/media/', callback: 'AdminDashboardController@media')
                        ->name('admin.media');

                $group->get(uri: "/{$loginRoute}/", callback: 'AdminAuthController@login')
                        ->name('admin.login')
                        ->middleware(['csrf.token']);

                $group->get(uri: '/logout/', callback: 'AdminAuthController@logout')
                        ->name('admin.logout')
                        ->middleware(['user.session.expire']);

                // Password Reset
                $group->get(uri: '/reset-password/', callback: 'AdminAuthController@resetPasswordView');
                $group->post(uri: '/reset-password/', callback: 'AdminAuthController@resetPasswordChange');

                // Plugin routes
                $group->get(uri: '/plugin/', callback: 'AdminPluginController@plugins')
                        ->name('admin.plugins');
                $group->get(uri: '/plugin/activate/', callback: 'AdminPluginController@activate');
                $group->get(uri: '/plugin/deactivate/', callback: 'AdminPluginController@deactivate');

                // Theme routes
                $group->get(uri: '/theme/', callback: 'AdminThemeController@themes')
                        ->name('admin.themes');
                $group->get(uri: '/theme/activate/', callback: 'AdminThemeController@activate');
                $group->get(uri: '/theme/deactivate/', callback: 'AdminThemeController@deactivate');


                // Content type routes
                $group->get(uri: '/content-type/', callback: 'AdminContentTypeController@contentTypes')
                        ->middleware(['csrf.token']);
                $group->post(uri: '/content-type/create/', callback: 'AdminContentTypeController@contentTypeCreate')
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/content-type/{contentTypeId}/',
                        callback: 'AdminContentTypeController@contentTypeView'
                )
                        ->where(['contentTypeId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token']);
                $group->post(
                        uri: '/content-type/{contentTypeId}/',
                        callback: 'AdminContentTypeController@contentTypeChange'
                )
                        ->where(['contentTypeId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/content-type/{contentTypeId}/d/',
                        callback: 'AdminContentTypeController@contentTypeDelete'
                )
                        ->where(['contentTypeId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);


                // Site routes
                $group->get(uri: '/site/', callback: 'AdminSiteController@sites')
                        ->middleware(['csrf.token']);
                $group->post(uri: '/site/', callback: 'AdminSiteController@siteCreate')
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(uri: '/site/users/', callback: 'AdminSiteController@siteUsers')
                        ->middleware(['csrf.token']);
                $group->post(uri: '/site/users/{userId}/d/', callback: 'AdminSiteController@siteUsersDelete')
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(uri: '/site/{siteId}/', callback: 'AdminSiteController@siteView')
                        ->where(['siteId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token']);
                $group->post(
                        uri: '/site/{siteId}/',
                        callback: 'AdminSiteController@siteChange'
                )
                        ->where(['siteId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/site/{siteId}/d/',
                        callback: 'AdminSiteController@siteDelete'
                )
                        ->where(['siteId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);


                // User routes
                $group->get(uri: '/user/', callback: 'AdminUserController@users')
                        ->middleware(['csrf.token']);
                $group->map(['GET', 'POST'], '/user/profile/', 'AdminUserController@userProfile')
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(uri: '/user/create/', callback: 'AdminUserController@userCreateView')
                        ->middleware(['csrf.token']);
                $group->post(uri: '/user/create/', callback: 'AdminUserController@userCreate')
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(uri: '/user/{userId}/', callback: 'AdminUserController@userView')
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token']);
                $group->post(
                        uri: '/user/{userId}/',
                        callback: 'AdminUserController@userChange'
                )
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->post(
                        uri: '/user/{userId}/d/',
                        callback: 'AdminUserController@userDelete'
                )
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->post(uri: '/user/lookup/', callback: 'AdminUserController@userLookup')
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/user/{userId}/reset-password/',
                        callback: 'AdminUserController@userResetPassword'
                )
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
                $group->get(uri: '/user/{userId}/switch-to/', callback: 'AdminUserController@userSwitchTo')
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
                $group->get(uri: '/user/{userId}/switch-back/', callback: 'AdminUserController@userSwitchBack')
                        ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);


                // Option routes
                $group->post(uri: '/options/', callback: 'AdminOptionsController@options');
                $group->get(uri: '/general/', callback: 'AdminOptionsController@generalView');
                $group->post(uri: '/general/', callback: 'AdminOptionsController@generalOptions');
                $group->get(uri: '/reading/', callback: 'AdminOptionsController@readingView');
                $group->post(uri: '/reading/', callback: 'AdminOptionsController@readingOptions');


                // Content routes
                $group->get(
                    uri: '/content-type/{contentTypeSlug}/',
                    callback: 'AdminContentController@contentViewByType'
                );
                $group->get(
                    uri: '/content-type/{contentTypeSlug}/create/',
                    callback: 'AdminContentController@contentCreateView'
                );
                $group->post(
                    uri: '/content-type/{contentTypeSlug}/create/',
                    callback: 'AdminContentController@contentCreate'
                );
                $group->get(
                        uri: '/content-type/{contentTypeSlug}/{contentId}/',
                        callback: 'AdminContentController@contentView'
                )
                        ->where(['contentId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token']);
                $group->post(
                        uri: '/content-type/{contentTypeSlug}/{contentId}/',
                        callback: 'AdminContentController@contentChange'
                )
                        ->where(['contentId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/content-type/{contentTypeSlug}/{contentId}/remove-featured-image/',
                        callback: 'AdminContentController@removeFeaturedImage'
                )
                        ->where(['contentId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
                $group->get(
                        uri: '/content-type/{contentTypeSlug}/{contentId}/d/',
                        callback: 'AdminContentController@contentDelete'
                )
                        ->where(['contentId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);


                // Product routes
                $group->get(uri: '/product/', callback: 'AdminProductController@products');
                $group->get(
                        uri: '/product/create/',
                        callback: 'AdminProductController@productCreateView'
                )
                        ->middleware(['csrf.token']);
                $group->post(
                        uri: '/product/create/',
                        callback: 'AdminProductController@productCreate'
                )
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/product/{productId}/',
                        callback: 'AdminProductController@productView'
                )
                        ->where(['productId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token',]);
                $group->post(
                        uri: '/product/{productId}/',
                        callback: 'AdminProductController@productChange'
                )
                        ->where(['productId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                        ->middleware(['csrf.token','csrf.protection']);
                $group->get(
                        uri: '/product/{productId}/remove-featured-image/',
                        callback: 'AdminProductController@removeFeaturedImage'
                )
                        ->where(['productId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
                $group->get(
                        uri: '/product/{productId}/d/',
                        callback: 'AdminProductController@productDelete'
                )
                        ->where(['productId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
            }
    );
};
