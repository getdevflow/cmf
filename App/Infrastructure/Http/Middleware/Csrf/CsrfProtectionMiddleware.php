<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware\Csrf;

use App\Infrastructure\Http\Middleware\Csrf\Traits\CsrfTokenAware;
use App\Shared\Http\RequestMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Session\SessionService;

final class CsrfProtectionMiddleware implements MiddlewareInterface
{
    use CsrfTokenAware;

    public function __construct(protected ConfigContainer $configContainer, protected SessionService $sessionService)
    {
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (true === $this->needsProtection($request) && false === $this->tokensMatch($request)) {
            return JsonResponseFactory::create(
                data: 'Bad CSRF Token.',
                status: $this->configContainer->getConfigKey('csrf.error_status_code')
            );
        }

        return $response;
    }

    /**
     * Check for methods not defined as safe.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function needsProtection(ServerRequestInterface $request): bool
    {
        return RequestMethod::isSafe($request->getMethod()) === false;
    }

    /**
     * @throws Exception
     */
    private function tokensMatch(ServerRequestInterface $request): bool
    {
        $expected = $this->fetchToken($request);
        $provided = $this->getTokenFromRequest($request);

        return hash_equals($expected, $provided);
    }


    /**
     * @throws Exception
     * @throws \Exception
     */
    private function fetchToken(ServerRequestInterface $request): string
    {
        $token = $request->getAttribute(CsrfTokenMiddleware::SESSION_ATTRIBUTE);

        // Ensure the token stored previously by the CsrfTokenMiddleware is present and has a valid format.
        if (
            is_string($token) &&
            ctype_alnum($token) &&
            strlen($token) === $this->configContainer->getConfigKey(key: 'csrf.csrf_token_length')
        ) {
            return $token;
        }

        return '';
    }

    /**
     * @throws Exception
     */
    private function getTokenFromRequest(ServerRequestInterface $request): string
    {
        if ($request->hasHeader($this->configContainer->getConfigKey(key: 'csrf.header'))) {
            return (string) $request->getHeaderLine($this->configContainer->getConfigKey(key: 'csrf.header'));
        }

        // Handle the case for a POST form.
        $body = $request->getParsedBody();

        if (
            is_array(
                $body
            ) &&
            isset($body[$this->configContainer->getConfigKey(key: 'csrf.csrf_token')]) &&
            is_string($body[$this->configContainer->getConfigKey(key: 'csrf.csrf_token')])
        ) {
            return $body[$this->configContainer->getConfigKey(key: 'csrf.csrf_token')];
        }

        return '';
    }
}
