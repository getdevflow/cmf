<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Application\Devflow;
use App\Domain\User\Model\User;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\NativePhpCookies;
use App\Infrastructure\Services\Options;
use App\Shared\Services\Registry;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\Framework\Auth\Rbac\Rbac;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Support\Password;
use Codefy\QueryBus\UnresolvableQueryHandlerException as UnresolvableQueryHandlerExceptionAlias;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use ReflectionException;

use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\storage_path;
use function file_exists;
use function filter_var;
use function parse_str;
use function Qubus\Routing\Helpers\redirect;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;
use function sprintf;
use function time;
use function unlink;

/**
 * @file App/Shared/Helpers/auth.php
 * @return array
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_roles(): array
{
    /** @var User $user */
    $user = get_current_user();

    $result = [];
    foreach ((array) $user->role as $roleName) {
        /** @var Rbac $rbac */
        $rbac = Registry::getInstance()->get('rbac');
        if ($role = $rbac->getRole($roleName)) {
            $result[$roleName] = $role;
        }
    }
    return $result;
}

/**
 * Checks if current user has specified permission or not.
 *
 * @file App/Shared/Helpers/auth.php
 * @param string $perm Permission to check for.
 * @param array $ruleParams (Optional) Other parameters to use for checking
 *                          based on a rule.
 * @return bool Return true if permission matches or false otherwise.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerExceptionAlias
 */
function current_user_can(string $perm, array $ruleParams = []): bool
{
    $currentUser = get_current_user();
    if (empty($currentUser)) {
        return false;
    }

    $roles = get_roles();
    foreach ($roles as $role) {
        if ($role->checkAccess($perm, $ruleParams)) {
            return true;
        }
    }

    return false;
}

/**
 * Checks if a visitor is logged in or not.
 *
 * @file App/Shared/Helpers/auth.php
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function is_user_logged_in(): bool
{
    if (!isset($_COOKIE['USERCOOKIEID'])) {
        return false;
    }

    $cookies = NativePhpCookies::factory();

    $user = get_user_by('token', get_current_user()->token);
    return false !== $user && $cookies->verifySecureCookie(key: 'USERCOOKIEID');
}

/**
 * Checks if logged-in user can access menu, tab, or screen.
 *
 * @file App/Shared/Helpers/auth.php
 * @param string $perm Permission to check for.
 * @return string HTML style.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerExceptionAlias
 */
function ae(string $perm): string
{
    if (!current_user_can($perm)) {
        return ' style="display:none !important;"';
    }

    return '';
}

/**
 * Logs a user in after the login information has checked out.
 *
 * @file App/Shared/Helpers/auth.php
 * @param string $login User's username or email address.
 * @param string $password User's password.
 * @param string $rememberme Whether to remember the user.
 * @return ResponseInterface
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 */
function cms_authenticate(string $login, string $password, string $rememberme): ResponseInterface
{
    $dfdb = Devflow::inst()->dfdb;

    $request = new ServerRequest();

    $sql = "SELECT *"
    . " FROM {$dfdb->basePrefix}user"
    . " WHERE user_login = ?"
    . " OR user_email = ?";

    $user = $dfdb->getRow($dfdb->prepare($sql, [$login, $login]), Database::ARRAY_A);

    if (is_false__($user)) {
        Devflow::$APP->flash->error(
            sprintf(
                t__(
                    msgid: 'Sorry, there was an error.',
                    domain: 'devflow'
                ),
                $login
            ),
        );
        return redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * Filters the authentication cookie.
     *
     * @file App/Shared/Helpers/auth.php
     * @param array $user User data array.
     * @param string $rememberme Whether to remember the user.
     */
    Filter::getInstance()->applyFilter('cms_auth_cookie', $user, $rememberme);

    $redirectTo = Filter::getInstance()->applyFilter(
        'authenticate_redirect_to',
        $request->getParsedBody()['redirect_to'] ?? admin_url()
    );

    Devflow::$APP->flash->success(
        sprintf(
            t__(
                msgid: 'Login was successful. Welcome <strong>%s</strong> to the admin dashboard.',
                domain: 'devflow'
            ),
            get_name(esc_html($user['user_id']))
        ),
    );

    return redirect($redirectTo);
}

/**
 * Checks a user's login information.
 *
 * @file App/Shared/Helpers/auth.php
 * @param string $login User's username or email address.
 * @param string $password User's password.
 * @param string $rememberme Whether to remember the user.
 * @return string|ResponseInterface Returns credentials if valid, null or false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 */
function cms_authenticate_user(string $login, string $password, string $rememberme): string|ResponseInterface
{
    $request = new ServerRequest();

    if (empty($login) || empty($password)) {
        if (empty($login)) {
            Devflow::$APP->flash->error(
                t__(
                    msgid: '<strong>ERROR</strong>: The username/email field is empty.',
                    domain: 'devflow'
                ),
            );
            return redirect($request->getServerParams()['HTTP_REFERER']);
        }

        if (empty($password)) {
            Devflow::$APP->flash->error(
                t__(
                    msgid: '<strong>ERROR</strong>: The password field is empty.',
                    domain: 'devflow'
                ),
            );
            return redirect($request->getServerParams()['HTTP_REFERER']);
        }
    }

    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $user = get_user_by('email', $login);

        if (is_false__($user)) {
            Devflow::$APP->flash->error(
                t__(
                    msgid: '<strong>ERROR</strong>: Invalid email address.',
                    domain: 'devflow'
                ),
            );
            return redirect($request->getServerParams()['HTTP_REFERER']);
        }
    } else {
        $user = get_user_by('login', $login);

        if (is_false__($user)) {
            Devflow::$APP->flash->error(
                t__(
                    msgid: '<strong>ERROR</strong>: Invalid username.',
                    domain: 'devflow'
                ),
            );
            return redirect($request->getServerParams()['HTTP_REFERER']);
        }
    }

    if (!Password::verify($password, $user->pass)) {
        Devflow::$APP->flash->error(
            t__(
                msgid: '<strong>ERROR</strong>: The password you entered is incorrect.',
                domain: 'devflow'
            ),
        );
        return redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * Filters log in details.
     *
     * @file App/Shared/Helpers/auth.php
     * @param string $login User's username or email address.
     * @param string $password User's password.
     * @param string $rememberme Whether to remember the user.
     */
    return Filter::getInstance()->applyFilter('cms_authenticate_user', $login, $password, $rememberme);
}

/**
 * Sets auth cookie.
 *
 * @file App/Shared/Helpers/auth.php
 * @param array $user User data array.
 * @param string $rememberme Should user be remembered for a length of time?
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function cms_set_auth_cookie(array $user, string $rememberme = ''): void
{
    $cookies = NativePhpCookies::factory();

    if (isset($rememberme)) {
        /**
         * Ensure the browser will continue to send the cookie until it expires.
         *
         * @file App/Shared/Helpers/auth.php
         */
        $expire = Filter::getInstance()->applyFilter(
            'auth_cookie_expiration',
            Options::factory()->read('cookieexpire', 172800)
        );
    } else {
        /**
         * Ensure the browser will continue to send the cookie until it expires.
         *
         * @file App/Shared/Helpers/auth.php
         */
        $expire = Filter::getInstance()->applyFilter(
            'auth_cookie_expiration',
            config('cookies.lifetime') ?? 86400
        );
    }

    $authCookie = [
        'key' => 'USERCOOKIEID',
        'id' => esc_html($user['user_id']),
        'token' => esc_html($user['user_token']),
        'remember' => (isset($rememberme) ? 'yes' : 'no'),
        'exp' => (int) $expire + time()
    ];

    /**
     * Fires immediately before the secure authentication cookie is set.
     *
     * @file App/Shared/Helpers/auth.php
     * @param array $authCookie Authentication cookie.
     * @param int   $expire  Duration in seconds the authentication cookie should be valid.
     */
    Action::getInstance()->doAction('set_auth_cookie', $authCookie, $expire);

    $cookies->setSecureCookie($authCookie);
}

/**
 * Removes all cookies associated with authentication.
 *
 * @file App/Shared/Helpers/auth.php
 * @throws ReflectionException
 * @throws Exception
 */
function cms_clear_auth_cookie(): void
{
    $cookies = NativePhpCookies::factory();
    /**
     * Fires just before the authentication cookies are cleared.
     *
     * @file App/Shared/Helpers/auth.php
     */
    Action::getInstance()->doAction('clear_auth_cookie');

    $vars1 = [];
    parse_str($cookies->get('USERCOOKIEID'), $vars1);
    /**
     * Checks to see if the cookie exists on the server.
     * If it exists, we need to delete it.
     */
    $file1 = storage_path(path: 'app/cookies/cookie.' . $vars1['data']);
    try {
        if (file_exists($file1)) {
            unlink($file1);
        }
    } catch (NotFoundException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'FILESTATE[%s]: File not found: %s',
                $e->getCode(),
                $e->getMessage()
            )
        );
    }

    if (isset($_COOKIE['SWITCH_USERBACK'])) {
        $vars2 = [];
        parse_str($cookies->get('SWITCH_USERBACK'), $vars2);
        /**
         * Checks to see if the cookie exists on the server.
         * If it exists, we need to delete it.
         */
        $file2 = storage_path(path: 'app/cookies/cookie.' . $vars2['data']);
        if (file_exists($file2)) {
            @unlink($file2);
        }

        $cookies->remove(key: 'SWITCH_USERBACK');
    }

    /**
     * After the cookie is removed from the server,
     * we know need to remove it from the browser and
     * redirect the user to the login page.
     */
    $cookies->remove(key: 'USERCOOKIEID');
}

/**
 * Shows error messages on login form.
 *
 * @file App/Shared/Helpers/auth.php
 */
function login_form_show_message(): void
{
    echo Filter::getInstance()->applyFilter('login_form_show_message', Devflow::$APP->flash->display());
}

/**
 * Retrieves data from a secure cookie.
 *
 * @file App/Shared/Helpers/auth.php
 * @param string $key COOKIE key.
 * @return false|array|object Cookie data or false.
 */
function get_secure_cookie_data(string $key): false|array|object
{
    $cookies = NativePhpCookies::factory();

    if ($cookies->verifySecureCookie($key)) {
        return $cookies->getSecureCookie($key);
    }
    return false;
}

function get_user_roles(): array
{
    return config(key: 'rbac.roles');
}
