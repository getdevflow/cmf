<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use Codefy\Framework\Support\CodefyServiceProvider;
use Psr\Http\Message\RequestInterface;
use Qubus\Exception\Exception;
use Qubus\Http\Request;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Router;

final class WebRouteServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws TooLateToAddNewRouteException
     * @throws Exception
     */
    public function boot(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        //Dynamic route for login.
        $loginRoute = $this->codefy->configContainer->getConfigKey(key: 'auth.login_route');

        /** @var Request $request */
        $request = $this->codefy->make(RequestInterface::class);

        /** @var Router $router*/
        $router = $this->codefy->make(name: 'router');

        $router->group(params: ['prefix' => '/admin'], callback: function ($group) use ($loginRoute) {
            $group->get(uri: '/', callback: 'AdminDashboardController@index')
                    ->name('admin.dashboard');

            $group->get(uri: '/snapshot/', callback: 'AdminDashboardController@snapshot')
                    ->name('admin.snapshot');

            $group->post(uri: '/auth/', callback: 'AdminAuthController@auth')
                    ->name('admin.auth')
                    ->middleware(['csrf.protection', 'user.authenticate','user.session']);

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


            $group->get(uri: '/content-type/', callback: 'AdminContentTypeController@contentTypes');
            $group->post(uri: '/content-type/create/', callback: 'AdminContentTypeController@contentTypeCreate')
                ->middleware(['csrf.protection']);
            $group->get(uri: '/content-type/{contentTypeId}/', callback: 'AdminContentTypeController@contentTypeView')
                ->where(['contentTypeId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->post(
                uri: '/content-type/{contentTypeId}/',
                callback: 'AdminContentTypeController@contentTypeChange'
            )
                ->where(['contentTypeId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                ->middleware(['csrf.protection']);
            $group->get(
                uri: '/content-type/{contentTypeId}/d/',
                callback: 'AdminContentTypeController@contentTypeDelete'
            )
            ->where(['contentTypeId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);

            $group->get(uri: '/site/', callback: 'AdminSiteController@sites');
            $group->post(uri: '/site/', callback: 'AdminSiteController@siteCreate')
                ->middleware(['csrf.protection']);
            $group->get(uri: '/site/users/', callback: 'AdminSiteController@siteUsers');
            $group->get(uri: '/site/users/{userId}/d/', callback: 'AdminSiteController@siteUsersDelete')
                ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
            $group->get(uri: '/site/{siteId}/', callback: 'AdminSiteController@siteView')
                ->where(['siteId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->post(
                uri: '/site/{siteId}/',
                callback: 'AdminSiteController@siteChange'
            )
                ->where(['siteId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                ->middleware(['csrf.protection']);
            $group->get(
                uri: '/site/{siteId}/d/',
                callback: 'AdminSiteController@siteDelete'
            )
                ->where(['siteId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);

            $group->get(uri: '/user/', callback: 'AdminUserController@users');
            $group->map(['GET', 'POST'], '/user/profile/', callback: 'AdminUserController@userProfile')
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->get(uri: '/user/create/', callback: 'AdminUserController@userCreateView')
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->post(uri: '/user/create/', callback: 'AdminUserController@userCreate')
                ->middleware(['csrf.protection']);
            $group->get(uri: '/user/{userId}/', callback: 'AdminUserController@userView')
                ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->post(
                uri: '/user/{userId}/',
                callback: 'AdminUserController@userChange'
            )
                ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                ->middleware(['csrf.protection']);
            $group->get(
                uri: '/user/{userId}/d/',
                callback: 'AdminUserController@userDelete'
            )
                ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
            $group->post(uri: '/user/lookup/', callback: 'AdminUserController@userLookup');
            $group->get(uri: '/user/{userId}/reset-password/', callback: 'AdminUserController@userResetPassword')
                    ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
            $group->get(uri: '/user/{userId}/switch-to/', callback: 'AdminUserController@userSwitchTo')
                    ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);
            $group->get(uri: '/user/{userId}/switch-back/', callback: 'AdminUserController@userSwitchBack')
                    ->where(['userId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+']);

            $group->post(uri: '/options/', callback: 'AdminOptionsController@options')
                ->middleware(['csrf.protection']);
            $group->get(uri: '/general/', callback: 'AdminOptionsController@generalView')
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->post(uri: '/general/', callback: 'AdminOptionsController@generalOptions')
                ->middleware(['csrf.protection']);
            $group->get(uri: '/reading/', callback: 'AdminOptionsController@readingView')
                ->middleware(['csrf.token', 'csrf.protection']);
            $group->post(uri: '/reading/', callback: 'AdminOptionsController@readingOptions')
                ->middleware(['csrf.protection']);

            $group->get(uri: '/content-type/{contentTypeSlug}/', callback: 'AdminContentController@contentViewByType');
            $group->get(
                uri: '/content-type/{contentTypeSlug}/create/',
                callback: 'AdminContentController@contentCreateView'
            )
                ->middleware(['csrf.token']);
            $group->post(
                uri: '/content-type/{contentTypeSlug}/create/',
                callback: 'AdminContentController@contentCreate'
            )
                ->middleware(['csrf.protection']);
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
                ->middleware(['csrf.protection']);
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
                    ->middleware(['csrf.protection']);
            $group->get(
                uri: '/product/{productId}/',
                callback: 'AdminProductController@productView'
            )
                    ->where(['productId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                    ->middleware(['csrf.token']);
            $group->post(
                uri: '/product/{productId}/',
                callback: 'AdminProductController@productChange'
            )
                    ->where(['productId' => '[0123456789ABCDEFGHJKMNPQRSTVWXYZ{26}$]+'])
                    ->middleware(['csrf.protection']);
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
        });

        /*
         * Set the default controller namespace for custom Devflow development.
         */
        $router->setDefaultNamespace('\\Cms\\Http\\Controllers');
        $router->get(uri: '/cron/', callback: 'CronController@cron');
        $cmsRoutes = $this->codefy->configContainer->getConfigKey(key: 'routes');

        if (!empty($cmsRoutes)) {
            foreach ($cmsRoutes as $host => $route) {
                if ($host === $request->getUri()->getHost()) {
                    $this->codefy->execute([$route, 'handle']);
                }
            }
        }
    }
}
