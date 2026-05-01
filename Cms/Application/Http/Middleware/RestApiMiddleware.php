<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Qubus\Expressive\Database;
use App\Infrastructure\Services\Options;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use ReflectionException;

use function Codefy\Framework\Helpers\trans;
use function sprintf;

class RestApiMiddleware implements MiddlewareInterface
{
    public function __construct(protected Database $dfdb, protected Options $option)
    {
    }

    /**
     * @inheritDoc
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            isset($request->getQueryParams()['key']) &&
                $request->getQueryParams()['key'] === $this->option->read(optionKey: 'api_key')
        ) {
            return $handler->handle($request);
        }

        if (
            str_starts_with($request->getHeaderLine('authorization'), 'Bearer ') &&
                $request->getHeaderLine('authorization') ===
                sprintf('Bearer %s', $this->option->read(optionKey: 'api_key'))
        ) {
            return $handler->handle($request);
        }

        return JsonResponseFactory::create(
            data: trans('Unauthorized.'),
            status: 401
        );
    }
}
