<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\User\Model\User;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Http\BaseController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
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
use function App\Shared\Helpers\cms_authenticate_user;
use function App\Shared\Helpers\cms_clear_auth_cookie;
use function App\Shared\Helpers\cms_update_user;
use function App\Shared\Helpers\generate_random_password;
use function App\Shared\Helpers\get_user_by;
use function App\Shared\Helpers\login_url;
use function App\Shared\Helpers\site_url;
use function Codefy\Framework\Helpers\config;
use function Qubus\Error\Helpers\is_error;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;

final class AdminAuthController extends BaseController
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
     * @return ResponseInterface
     * @throws SessionException
     */
    public function auth(ServerRequest $request): ResponseInterface
    {
        try {
            /**
             * Filters where the admin should be redirected after successful login.
             */
            $loginLink = Filter::getInstance()->applyFilter(
                'admin_login_redirect',
                admin_url()
            );

            cms_authenticate_user(
                login: $request->getParsedBody()['user_login'],
                password: $request->getParsedBody()['user_pass'],
                rememberme: $request->getParsedBody()['rememberme'] ?? 'no'
            );

            /**
             * Fires after the user has logged in.
             *
             * @param $sessionService SessionService
             * @param $request ServerRequest
             */
            Action::getInstance()->doAction('login_init', $this->sessionService, $request);

            return $this->redirect($loginLink);
        } catch (
            NotFoundExceptionInterface |
            ContainerExceptionInterface |
            InvalidArgumentException |
            SessionException |
            Exception |
            ReflectionException $e
        ) {
            Devflow::inst()::$APP->flash->error($e->getMessage());
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
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
     * @throws TypeException
     */
    public function login(ServerRequest $request): ResponseInterface|string
    {
        if (true === $this->user->can(permissionName: 'access:admin', request: $request)) {
            return $this->redirect(admin_url());
        }

        return $this->view->render(
            template: 'framework::backend/auth/index',
            data: [
                'title' => t__(msgid: 'Login', domain: 'devflow'),
                'url' => site_url($this->router->url(name: 'admin.auth')),
            ]
        );
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface
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
    public function logout(ServerRequest $request): ResponseInterface
    {
        if (
            isset($request->getServerParams()['HTTP_REFERER']) &&
                !str_contains($request->getServerParams()['HTTP_REFERER'], 'admin')
        ) {
            $logoutLink = Filter::getInstance()->applyFilter(
                'user_logout_redirect',
                login_url()
            );
        } else {
            $logoutLink = Filter::getInstance()->applyFilter(
                'admin_logout_redirect',
                admin_url()
            );
        }

        if (false === $this->user->can(permissionName: 'access:admin', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'You are already logged out.', domain: 'devflow')
            );
            return $this->redirect($logoutLink);
        }

        /**
         * This function is documented in App/Shared/Helpers/auth.php.
         */
        cms_clear_auth_cookie();

        /**
         * Fires after a user has logged out.
         *
         * @param $sessionService SessionService
         * @param $request ServerRequest
         */
        Action::getInstance()->doAction('cms_logout', $this->sessionService, $request);

        return $this->redirect($this->router->url(name: 'admin.login'));
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws SessionException
     * @throws ReflectionException
     */
    public function resetPasswordChange(ServerRequest $request): ResponseInterface
    {
        try {
            $user = get_user_by('email', $request->getParsedBody()['email']);
            if (is_false__($user)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Request error.', domain: 'devflow')
                );

                return $this->redirect($request->getServerParams()['HTTP_REFERER']);
            }

            if ('' !== $user->id) {
                $password = generate_random_password(config(key: 'cms.password_length'));
                $_user = (new User($this->dfdb))->findBy(field: 'email', value: $user->email);

                foreach ($user->toArray() as $key => $value) {
                    unset($_user->pass);
                    $_user->{$key} = $value;
                }
                $_user->pass = $password;
                $update = cms_update_user($_user);

                if (is_error($update)) {
                    Devflow::inst()::$APP->flash->error(
                        message: t__(msgid: 'Request error.', domain: 'devflow')
                    );

                    return $this->redirect($request->getServerParams()['HTTP_REFERER']);
                } else {
                    Devflow::inst()::$APP->flash->success(
                        message: t__(
                            msgid: 'A new password was sent to your email. May take a few minutes to arrive, 
                                so please be patient',
                            domain: 'devflow'
                        )
                    );
                }
            }

            return $this->redirect($this->router->url(name: 'admin.login'));
        } catch (\Exception | NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                t__(msgid: 'Password reset exception occurred and was logged.', domain: 'devflow')
            );
            return $this->redirect($request->getServerParams()['HTTP_REFERER']);
        }
    }

    public function resetPasswordView(): ?string
    {
        return $this->view->render('framework::backend/auth/reset');
    }
}
