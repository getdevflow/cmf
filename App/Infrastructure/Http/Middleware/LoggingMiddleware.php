<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Codefy\Framework\Factory\FileLoggerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

final class LoggingMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        FileLoggerFactory::getLogger()->info(
            sprintf(
                '[REQUEST] HTTP %s %s: uri: %s, length: %d',
                $request->getProtocolVersion(),
                $request->getMethod(),
                $request->getUri(),
                $request->getBody()->getSize()
            ),
            $request->getHeaders()
        );

        $response = $handler->handle($request);

        FileLoggerFactory::getLogger()->info(
            sprintf(
                '[RESPONSE] status %d, length: %d',
                $response->getStatusCode(),
                $response->getBody()->getSize()
            )
        );

        return $response;
    }
}
