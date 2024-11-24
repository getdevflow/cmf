<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\User\Model\User;
use App\Infrastructure\Http\Middleware\UserAuthorizationMiddleware;
use Codefy\Framework\Auth\Rbac\Rbac;
use Codefy\Framework\Auth\UserSession;
use Codefy\Framework\Factory\FileLoggerFactory;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Session\SessionService;
use ReflectionException;

use function App\Shared\Helpers\get_user_by;
use function Qubus\Support\Helpers\is_false__;

final class UserAuth
{
    private ?string $token = null;
    public function __construct(
        protected Rbac $rbac,
        protected SessionService $sessionService,
        protected ServerRequestInterface $request
    ) {
    }

    /**
     * Returns whether the current user has the specified permission.
     *
     * @param string $permissionName
     * @param ServerRequestInterface $request
     * @param array $ruleParams
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    public function can(string $permissionName, ServerRequestInterface $request, array $ruleParams = []): bool
    {
        $this->setRequest($request);

        /** This is only checked by routes which have the `user.authorization` middleware enabled. */
        if ($this->request->getHeaderLine(UserAuthorizationMiddleware::HEADER_HTTP_STATUS_CODE) === 'not_authorized') {
            return false;
        }

        if (
            !isset($this->request->getCookieParams()['USERSESSID'])
                || empty($this->request->getCookieParams()['USERSESSID'])
        ) {
            return false;
        }

        $roles = $this->getRoles();
        foreach ($roles as $role) {
            if ($role->checkAccess($permissionName, $ruleParams)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return User|null
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     * @throws Exception
     */
    public function current(): ?User
    {
        $this->sessionService::$options = [
            'cookie-name' => 'USERSESSID',
        ];
        $session = $this->sessionService->makeSession($this->request);

        /** @var UserSession $user */
        $user = $session->get(type: CmsUserSession::class);
        if ($user->isEmpty()) {
            return null;
        }

        $this->token = $user->token;

        try {
            return $this->findUserByToken();
        } catch (ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
        }

        return null;
    }

    /**
     * @return User|null
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws \Qubus\Exception\Exception
     */
    public function findUserByToken(): ?User
    {
        /** @var User $user */
        $user = get_user_by('token', $this->token);
        if (is_false__($user)) {
            return null;
        }

        return $user;
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws \Qubus\Exception\Exception
     */
    private function getRoles(): array
    {
        $user = $this->current();
        $result = [];
        foreach ((array)$user->role as $roleName) {
            if ($role = $this->rbac->getRole($roleName)) {
                $result[$roleName] = $role;
            }
        }
        return $result;
    }

    private function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
