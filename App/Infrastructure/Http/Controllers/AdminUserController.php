<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\User\Model\User;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\CmsUserSession;
use App\Infrastructure\Services\NativePhpCookies;
use App\Infrastructure\Services\UserAuth;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Http\BaseController;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Config\ConfigContainer;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Response;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\cms_delete_user;
use function App\Shared\Helpers\cms_insert_user;
use function App\Shared\Helpers\cms_update_user;
use function App\Shared\Helpers\get_all_users;
use function App\Shared\Helpers\get_current_user_id;
use function App\Shared\Helpers\get_name;
use function App\Shared\Helpers\get_user_by;
use function App\Shared\Helpers\get_user_value;
use function App\Shared\Helpers\get_userdata;
use function App\Shared\Helpers\is_user_logged_in;
use function App\Shared\Helpers\queue_new_user_email;
use function App\Shared\Helpers\reset_password;
use function array_merge;
use function Codefy\Framework\Helpers\storage_path;
use function file_exists;
use function get_class;
use function parse_str;
use function Qubus\Error\Helpers\is_error;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;
use function sprintf;
use function strlen;
use function time;
use function unlink;

final class AdminUserController extends BaseController
{
    public function __construct(
        SessionService $sessionService,
        Router $router,
        protected Database $dfdb,
        protected UserAuth $user,
        protected ConfigContainer $configContainer,
        ?Renderer $view = null
    ) {
        parent::__construct($sessionService, $router, $view);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function userCreate(ServerRequest $request): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'create:users', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        if (strlen($request->get('pass')) < (int) $this->configContainer->getConfigKey(key: 'cms.password_length')) {
            Devflow::inst()::$APP->flash->error(
                message: sprintf(
                    esc_html__(string: 'Passwords cannot be less than %s characters', domain: 'devflow'),
                    $this->configContainer->getConfigKey(key: 'cms.password_length')
                ),
            );
            $this->redirect($request->getServerParams()['HTTP_REFERER']);
        }

        /** @var User $user */
        $user = get_user_by(field: 'email', value: $request->get('email'));

        if (is_false__($user)) {
            $update = false;
            $userLogin = $request->get('login');
            $extra = ['pass' => $request->get('pass')];
        } else {
            $update = true;
            $userLogin = $user->login;
            $extra = ['pass' => $request->get('pass'), 'login' => $userLogin];
        }

        if (empty($userLogin)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Username cannot be empty or null.', domain: 'devflow'),
            );
            return $this->redirect($request->getServerParams()['HTTP_REFERER']);
        }

        try {
            $arrayMerge = array_merge($extra, $request->getParsedBody());
            if ($update) {
                $userId = cms_update_user($arrayMerge);
            } else {
                $userId = cms_insert_user($arrayMerge);
            }

            if (is_error($userId)) {
                Devflow::inst()::$APP->flash->error(
                    message: $userId->getMessage(),
                );
                return $this->redirect($request->getServerParams()['HTTP_REFERER']);
            }

            /** Set user's role */
            $newUser = new User();
            $newUser->id = $userId;
            $newUser->setRole($request->get('role'));

            if ($request->get('sendemail') === 1) {
                queue_new_user_email($userId, $request->get('pass'));
            }

            Devflow::inst()::$APP->flash->success(
                message: Devflow::inst()::$APP->flash->notice(num: 201),
            );

            $request->withHeader('USER_BODY', $request->getParsedBody());

            return $this->redirect(admin_url("user/$userId/"));
        } catch (
            CommandPropertyNotFoundException |
            UnresolvableQueryHandlerException |
            TypeException |
            Exception |
            EnvironmentIsBrokenException |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error(
                $e->getMessage(),
                ['code' => $e->getCode(), 'exception' => get_class($e)]
            );
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Insertion exception occurred.', domain: 'devflow')
            );
        }

        return JsonResponseFactory::create(t__(msgid: 'Insertion error.', domain: 'devflow'), 404);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function userCreateView(ServerRequest $request): ResponseInterface|string
    {
        if (false === $this->user->can(permissionName: 'create:users', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        return $this->view->render(
            template: 'framework::backend/admin/user/create',
            data: [
                'title' => t__(msgid: 'Create User', domain: 'devflow'),
                'request' => $request->getHeaderLine('USER_BODY')
            ]
        );
    }

    /**
     * @param ServerRequest $request
     * @return string|ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function users(ServerRequest $request): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'manage:users', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        try {
            $users = get_all_users();

            return $this->view->render(
                template: 'framework::backend/admin/user/index',
                data: [
                    'title' => esc_html__(string: 'Users', domain: 'devflow'),
                    'users' => $users,
                ]
            );
        } catch (UnresolvableQueryHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Query error.', domain: 'devflow')
            );
        }

        return JsonResponseFactory::create(data: t__(msgid: 'Users error', domain: 'devflow'), status: 404);
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function userChange(ServerRequest $request, string $userId): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:users', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Permission denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        /**
         * Action triggered before user record is updated.
         */
        Action::getInstance()->doAction('pre_update_user', $userId);

        $dataArrayMerge = array_merge(['id' => $userId], $request->getParsedBody());

        try {
            $userId = cms_update_user($dataArrayMerge);

            if (is_error($userId)) {
                Devflow::inst()::$APP->flash->error(
                    message: $userId->getMessage(),
                );
                return $this->redirect($request->getServerParams()['HTTP_REFERER']);
            }

            /** Set user's role. */
            $newUser = new User();
            $newUser->id = $userId;
            $newUser->setRole($request->get('role'));

            Devflow::inst()::$APP->flash->success(
                message: Devflow::inst()::$APP->flash->notice(num: 201),
            );
        } catch (
            CommandPropertyNotFoundException |
            UnresolvableQueryHandlerException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Change error.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @return string|ResponseInterface
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     * @throws \Qubus\Exception\Exception
     * @throws Exception
     */
    public function userView(ServerRequest $request, string $userId): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            /** @var User $user */
            $user = get_user_by('id', $userId);

            return $this->view->render(
                template: 'framework::backend/admin/user/view',
                data: [
                    'title' => t__(msgid: 'View User', domain: 'devflow'),
                    'user' => $user,
                ]
            );
        } catch (
            CommandPropertyNotFoundException |
            UnresolvableQueryHandlerException |
            TypeException |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
        }

        return JsonResponseFactory::create(
            data: t__(msgid: 'The user does not exist.', domain: 'devflow'),
            status: 404
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function userDelete(ServerRequest $request, string $userId): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'delete:users', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $delete = cms_delete_user($userId);

            if (is_error($delete) || is_false__($delete)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'A deletion error occurred.', domain: 'devflow')
                );
            } else {
                Devflow::inst()::$APP->flash->success(
                    message: Devflow::inst()::$APP->flash->notice(num: 201),
                );
            }
        } catch (
            CommandPropertyNotFoundException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'A deletion error occurred.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @return false|string
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws \Qubus\Exception\Exception
     */
    public function userLookup(ServerRequest $request): false|string
    {
        if (!is_user_logged_in()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
        }

        $user = $this->dfdb->getRow(
            $this->dfdb->prepare(
                "SELECT user_fname, user_lname, user_email FROM {$this->dfdb->basePrefix}user WHERE user_id = ?",
                [
                    $request->getParsedBody()['id']
                ]
            ),
            Database::ARRAY_A
        );

        $json = [
            'input#fname' => esc_html($user['user_fname']), 'input#lname' => esc_html($user['user_lname']),
            'input#email' => esc_html($user['user_email'])
        ];
        return json_encode($json);
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @return ResponseInterface
     * @throws CommandCouldNotBeHandledException
     * @throws ContainerExceptionInterface
     * @throws EnvironmentIsBrokenException
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableCommandHandlerException
     * @throws \Qubus\Exception\Exception
     */
    public function userResetPassword(ServerRequest $request, string $userId): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:users', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
        }

        $user = get_user_by('id', $userId);
        if (is_false__($user)) {
            Devflow::inst()::$APP->flash->error(t__(msgid: 'User not found.', domain: 'devflow'));
        }

        $_userId = reset_password($userId);
        if (is_string($_userId) && !is_false__($_userId)) {
            Devflow::inst()::$APP->flash->success(
                sprintf(
                    t__(msgid: 'Password successfully updated for <strong>%s</strong>.', domain: 'devflow'),
                    get_name($_userId)
                ),
            );
        }
        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @return string|ResponseInterface
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     * @throws \Qubus\Exception\Exception
     */
    public function userProfile(ServerRequest $request): string|ResponseInterface
    {
        if (!is_user_logged_in()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
        }

        if ($request->getMethod() == 'POST') {
            $userId = cms_update_user($request->getParsedBody());

            if (is_error($userId)) {
                Devflow::inst()::$APP->flash->error(t__(msgid: 'An error occurred.', domain: 'devflow'));
                return $this->redirect(admin_url('user/profile/'));
            }

            $user = new User();
            $user->id = $userId;
            $user->setRole($request->get('role'));

            Devflow::inst()::$APP->flash->success(message: Devflow::inst()::$APP->flash->notice(num: 201));
        }

        return $this->view->render(
            template: 'framework::backend/admin/user/profile',
            data: [
                'title' => t__(msgid: 'User Profile', domain: 'devflow'),
                'user' => get_userdata(get_current_user_id())
            ]
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @param Response $response
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function userSwitchTo(ServerRequest $request, string $userId, Response $response): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'switch:user', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
        }

        $cookies = NativePhpCookies::factory();
        if (isset($request->getCookieParams()['USERCOOKIEID'])) {
            $switchCookie = [
                'key' => 'SWITCH_USERBACK',
                'id' => get_current_user_id(),
                'token' => get_userdata(get_current_user_id())->token,
                'remember' => 'yes',
                'exp' => (int) $this->configContainer->getConfigKey('cookies.lifetime') ?? 86400 + time()
            ];

            $cookies->setSecureCookie($switchCookie);
        }

        $vars = [];
        parse_str($cookies->get('USERCOOKIEID'), $vars);

        /**
         * Checks to see if the cookie exists on the server.
         * If it exists, we need to delete it.
         */
        $file = storage_path('app/cookies/cookie.' . $vars['data']);
        if (file_exists($file)) {
            unlink($file);
        }
        /**
         * Delete the old cookies.
         */
        $cookies->remove('USERCOOKIEID');

        $authCookie = [
            'key' => 'USERCOOKIEID',
            'id' => $userId,
            'token' => get_user_value($userId, 'token'),
            'remember' => 'yes',
            'exp' => (int) $this->configContainer->getConfigKey('cookies.lifetime') ?? 86400 + time()
        ];

        $cookies->setSecureCookie($authCookie);

        $this->sessionService::$options = [
            'cookie-name' => 'USERSESSID',
            'cookie-lifetime' => (int) $this->configContainer->getConfigKey(key: 'cookies.lifetime') ?? 86400,
        ];
        $session = $this->sessionService->makeSession($request);

        /** @var CmsUserSession $user */
        $user = $session->get(type: CmsUserSession::class);
        $user
            ->withToken(token: get_user_value($userId, 'token'));

        $this->sessionService->commitSession($response, $session);

        Devflow::inst()::$APP->flash->success(message: t__(msgid: 'User switching was successful.', domain: 'devflow'));

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @param Response $response
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     * @throws Exception
     */
    public function userSwitchBack(ServerRequest $request, string $userId, Response $response): ResponseInterface
    {
        if (!is_user_logged_in()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
        }

        $cookies = NativePhpCookies::factory();

        if ($cookies->get('USERCOOKIEID') === null || $cookies->get('USERCOOKIEID') === '') {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Cookie is not properly set for user switching.', domain: 'devflow')
            );

            $this->redirect(admin_url());
        }

        $vars1 = [];
        parse_str($cookies->get('USERCOOKIEID'), $vars1);
        /**
         * Checks to see if the cookie exists on the server.
         * If it exists, we need to delete it.
         */
        $file1 = storage_path('app/cookies/cookie' . $vars1['data']);
        if (file_exists($file1)) {
            unlink($file1);
        }
        $cookies->remove('USERCOOKIEID');

        $vars2 = [];
        parse_str($cookies->get('SWITCH_USERBACK'), $vars2);
        /**
         * Checks to see if the cookie exists on the server.
         * If it exists, we need to delete it.
         */
        $file2 = storage_path('app/cookies/cookie' . $vars2['data']);
        if (file_exists($file2)) {
            unlink($file2);
        }
        $cookies->remove('SWITCH_USERBACK');

        /**
         * After the login as user cookies has been
         * removed from the server and the browser,
         * we need to set fresh cookies for the
         * original logged-in user.
         */
        $switchCookie = [
            'key' => 'USERCOOKIEID',
            'id' => $userId,
            'token' => get_user_value($userId, 'token'),
            'remember' => 'yes',
            'exp' => (int) $this->configContainer->getConfigKey('cookies.lifetime') ?? 86400 + time()
        ];
        $cookies->setSecureCookie($switchCookie);

        $this->sessionService::$options = [
            'cookie-name' => 'USERSESSID',
            'cookie-lifetime' => (int) $this->configContainer->getConfigKey(key: 'cookies.lifetime') ?? 86400,
        ];
        $session = $this->sessionService->makeSession($request);

        /** @var CmsUserSession $user */
        $user = $session->get(type: CmsUserSession::class);
        $user
            ->withToken(token: get_user_value($userId, 'token'));

        $this->sessionService->commitSession($response, $session);

        Devflow::inst()::$APP->flash->success(
            message: t__(msgid: 'Switching back to previous user session was successful.', domain: 'devflow')
        );

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }
}
