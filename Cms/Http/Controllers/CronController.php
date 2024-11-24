<?php

declare(strict_types=1);

namespace Cms\Http\Controllers;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Codefy\Framework\Http\BaseController;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\cms_nodeq_login_details;
use function App\Shared\Helpers\cms_nodeq_reset_password;

final class CronController extends BaseController
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
     * @return void
     */
    public function master(ServerRequest $request): void
    {
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws EnvironmentIsBrokenException
     */
    public function cron(): void
    {
        cms_nodeq_login_details();
        cms_nodeq_reset_password();
    }
}
