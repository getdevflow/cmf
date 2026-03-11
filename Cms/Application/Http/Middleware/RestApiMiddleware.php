<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\Options;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use ReflectionException;

use function Qubus\Security\Helpers\t__;
use function sprintf;

class RestApiMiddleware implements MiddlewareInterface
{
    public function __construct(protected Database $dfdb, ConfigContainer $configContainer)
    {
    }

    /**
     * @inheritDoc
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            isset($request->getQueryParams()['key']) &&
                $request->getQueryParams()['key'] === Options::factory()->read('api_key')
        ) {
            return $handler->handle($request);
        }

        if (
            str_starts_with($request->getHeaderLine('authorization'), 'Bearer ') &&
                $request->getHeaderLine('authorization') ===
                sprintf('Bearer %s', Options::factory()->read(optionKey: 'api_key'))
        ) {
            return $handler->handle($request);
        }

        return JsonResponseFactory::create(
            data: t__(msgid: 'Unauthorized.', domain: 'devflow'),
            status: 401
        );
    }
}
