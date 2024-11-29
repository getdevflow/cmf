<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Codefy\Framework\Http\BaseController;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Router;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\get_all_users;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;

final class AdminDashboardController extends BaseController
{
    public function __construct(
        SessionService $sessionService,
        Router $router,
        protected UserAuth $user,
        protected Database $dfdb,
        ?Renderer $view = null
    ) {
        parent::__construct($sessionService, $router, $view);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|string
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NamedRouteNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws RouteParamFailedConstraintException
     * @throws SessionException
     * @throws TypeException
     */
    public function index(ServerRequest $request): ResponseInterface|string
    {
        if (false === $this->user->can(permissionName: 'access:admin', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect($this->router->url(name: 'admin.login'));
        }

        return $this->view->render(template: 'framework::backend/index', data: ['title' => 'Admin Dashboard']);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|string
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NamedRouteNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws RouteParamFailedConstraintException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function snapshot(ServerRequest $request): ResponseInterface|string
    {
        if (false === $this->user->can(permissionName: 'access:admin', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect($this->router->url(name: 'admin.login'));
        }

        $users = get_all_users();

        return $this->view->render(
            template: 'framework::backend/snapshot',
            data: ['title' => t__(msgid: 'System Snapshot', domain: 'devflow'), 'users' => count($users)]
        );
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function flushCache(ServerRequest $request): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'manage:settings', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
        }

        if (true === SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'usermeta')->clear()) {
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'content')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'contentslug')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'contenttype')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'contentmeta')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'products')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'productslug')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'productsku')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'productmeta')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: 'useremail')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: 'userlogin')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: 'users')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: 'usertoken')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'options')->clear();
            SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'database')->clear();
            Devflow::inst()::$APP->flash->success(
                esc_html__(string: 'Cache flushed successfully.', domain: 'devflow')
            );
        }

        /**
         * Fires after cache has been flushed.
         */
        Action::getInstance()->doAction('flush_cache');

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|string
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function media(ServerRequest $request): ResponseInterface|string
    {
        if (false === $this->user->can(permissionName: 'manage:media', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );

            return $this->redirect(admin_url());
        }

        return $this->view->render(template: 'framework::backend/media', data: ['title' => 'Media Library']);
    }
}
