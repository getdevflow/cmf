<?php

declare(strict_types=1);

namespace Cms\Http\Middleware;

use App\Infrastructure\Services\CmsUserSession;
use App\Infrastructure\Services\Options;
use Codefy\Framework\Auth\Middleware\AuthenticationMiddleware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionService;
use ReflectionException;

final class CmsUserSessionMiddleware implements MiddlewareInterface
{
    public const string SESSION_ATTRIBUTE = 'USERSESSION';

    public function __construct(protected ConfigContainer $configContainer, protected SessionService $sessionService)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     * @throws TypeException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userDetails = $request->getAttribute(AuthenticationMiddleware::AUTH_ATTRIBUTE);

        $expire = isset($request->getParsedBody()['rememberme']) && $request->getParsedBody()['rememberme'] === 'yes' ?
        Options::factory()->read('cookieexpire', 172800) :
        $this->configContainer->getConfigKey(key: 'cookies.lifetime', default: 86400);

        $this->sessionService::$options = [
            'cookie-name' => 'USERSESSID',
            'cookie-lifetime' => (int) $expire,
        ];
        $session = $this->sessionService->makeSession($request);

        /** @var CmsUserSession $user */
        $user = $session->get(type: CmsUserSession::class);
        $user
            ->withToken(token: $userDetails->token);

        $request = $request->withAttribute(self::SESSION_ATTRIBUTE, $user);

        $response = $handler->handle($request);

        return $this->sessionService->commitSession($response, $session);
    }
}
