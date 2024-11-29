<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Codefy\Framework\Http\BaseController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\activate_plugin;
use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\deactivate_plugin;
use function App\Shared\Helpers\is_user_logged_in;
use function Qubus\Security\Helpers\t__;

final class AdminPluginController extends BaseController
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
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function plugins(ServerRequest $request): ResponseInterface|string
    {
        if (false === $this->user->can(permissionName: 'manage:plugins', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );

            return $this->redirect(admin_url());
        }

        return $this->view->render(template: 'framework::backend/admin/plugin/index', data: ['title' => 'Plugins']);
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
     */
    public function activate(ServerRequest $request): ResponseInterface
    {
        if (false === is_user_logged_in()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );

            return $this->redirect(admin_url());
        }

        activate_plugin($request->getQueryParams()['id']);

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
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
     */
    public function deactivate(ServerRequest $request): ResponseInterface
    {
        if (false === is_user_logged_in()) {
            Devflow::inst()::$APP->flash->error(message: t__(msgid: 'Access denied.', domain: 'devflow'));

            return $this->redirect(admin_url());
        }

        deactivate_plugin($request->getQueryParams()['id']);

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }
}
