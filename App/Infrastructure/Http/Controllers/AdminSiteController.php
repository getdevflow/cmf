<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\Site\Model\Site;
use App\Domain\User\Query\FindUsersQuery;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Codefy\CommandBus\Containers\ContainerFactory;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Http\BaseController;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use DateInvalidTimeZoneException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\cms_delete_site;
use function App\Shared\Helpers\cms_delete_site_user;
use function App\Shared\Helpers\cms_insert_site;
use function App\Shared\Helpers\cms_update_site;
use function App\Shared\Helpers\get_all_sites;
use function App\Shared\Helpers\get_site_by;
use function App\Shared\Helpers\is_multisite;
use function App\Shared\Helpers\sort_list;
use function Codefy\Framework\Helpers\config;
use function Qubus\Error\Helpers\is_error;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;

final class AdminSiteController extends BaseController
{
    public function __construct(
        SessionService $sessionService,
        Router $router,
        protected Database $dfdb,
        protected UserAuth $user,
        ?Renderer $view = null
    ) {
        parent::__construct($sessionService, $router, $view);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|null
     * @throws CommandCouldNotBeHandledException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableCommandHandlerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|DateInvalidTimeZoneException
     */
    public function siteCreate(ServerRequest $request): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'create:sites', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $id = cms_insert_site($request);
            if (is_error($id)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Insertion error occurred.', domain: 'devflow')
                );
            }

            Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 201));
        } catch (
            CommandPropertyNotFoundException |
            UnresolvableQueryHandlerException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Insertion exception occurred.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @return string|ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Exception
     */
    public function sites(ServerRequest $request): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'manage:sites', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $connection = config(key: 'database.default');

        try {
            $sites = get_all_sites();

            return $this->view->render(
                template: 'framework::backend/admin/site/index',
                data: [
                    'title' => t__(msgid: 'Sites', domain: 'devflow'),
                    'sites' => $sites,
                    'request' => $request,
                    'connection' => $connection,
                ]
            );
        } catch (UnresolvableQueryHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Query error.', domain: 'devflow')
            );
        }

        return JsonResponseFactory::create(data: t__(msgid: 'Content types error.', domain: 'devflow'), status: 404);
    }

    /**
     * @param ServerRequest $request
     * @param string $siteId
     * @return ResponseInterface|null
     * @throws CommandCouldNotBeHandledException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableCommandHandlerException|DateInvalidTimeZoneException
     */
    public function siteChange(ServerRequest $request, string $siteId): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:sites', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $dataArrayMerge = array_merge(['id' => $siteId], $request->getParsedBody());

        try {
            $id = cms_update_site($dataArrayMerge);
            if (is_error($id)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Change error occurred.', domain: 'devflow')
                );
            }

            Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 200));
        } catch (
            CommandPropertyNotFoundException |
            UnresolvableQueryHandlerException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Change exception occurred.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $siteId
     * @return string|ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Exception
     */
    public function siteView(ServerRequest $request, string $siteId): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'manage:sites', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            /** @var Site $site */
            $site = get_site_by(field: 'id', value: $siteId);

            if (is_false__($site)) {
                return JsonResponseFactory::create(
                    data: t__(msgid: 'The site does not exist.', domain: 'devflow'),
                    status: 404
                );
            }

            return $this->view->render(
                template: 'framework::backend/admin/site/view',
                data: [
                    'title' => $site->name,
                    'site' => $site,
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
            data: t__(msgid: 'The site does not exist.', domain: 'devflow'),
            status: 404
        );
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|string|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function siteUsers(ServerRequest $request): ResponseInterface|null|string
    {
        if (false === $this->user->can(permissionName: 'manage:sites', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $resolver = new NativeQueryHandlerResolver(container: ContainerFactory::make(config: []));
        $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

        $query = new FindUsersQuery();
        $results = $enquirer->execute($query);

        $users = sort_list($results, 'lname', 'ASC', true);

        return $this->view->render(
            template: 'framework::backend/admin/site/users',
            data: [
                'title' => t__(msgid: 'Manage Site Users', domain: 'devflow'),
                'users' => $users,
            ]
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $userId
     * @return ResponseInterface
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function siteUsersDelete(ServerRequest $request, string $userId): ResponseInterface
    {
        if (
            false === $this->user->can(permissionName: 'delete:users', request: $request) &&
            false === $this->user->can(permissionName: 'manage:sites', request: $request)
        ) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url('site/users/'));
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (isset($request->getParsedBody()['assign_id']) && '' !== $request->getParsedBody()['assign_id']) {
            $siteUser = cms_delete_site_user(
                $userId,
                [
                    'assign_id' => $request->getParsedBody()['assign_id'],
                    'role' => $request->getParsedBody()['role'],
                ]
            );
        } else {
            $siteUser = cms_delete_site_user($userId);
        }

        if (is_error($siteUser)) {
            Devflow::inst()::$APP->flash->error(
                sprintf(
                    'ERROR[%s]: %s',
                    $siteUser->getCode(),
                    $siteUser->getMessage()
                ),
            );
        } else {
            Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 200));
        }

        return $this->redirect(admin_url('site/users/'));
    }

    /**
     * @param ServerRequest $request
     * @param string $siteId
     * @return ResponseInterface
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function siteDelete(ServerRequest $request, string $siteId): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'delete:sites', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        if (!is_multisite()) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $connection = config(key: 'database.default');

        /** @var Site $checkSite */
        $checkSite = get_site_by('id', $siteId);

        if (
                $checkSite->key === config(key: "database.connections.$connection.prefix") ||
                $checkSite->domain === config(key: 'cms.main_site_url')
        ) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'This action is not allowed.', domain: 'devflow')
            );
            return $this->redirect(admin_url('site/'));
        }

        try {
            $delete = cms_delete_site($siteId);

            if (is_error($delete) || is_false__($delete)) {
                Devflow::inst()::$APP->flash->error(
                    message: Devflow::inst()::$APP->flash->notice(num: 201)
                );
            }
        } catch (
            CommandCouldNotBeHandledException |
            CommandPropertyNotFoundException |
            UnresolvableCommandHandlerException |
            UnresolvableQueryHandlerException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'A deletion exception occurred.', domain: 'devflow')
            );
        }

        return $this->redirect(admin_url('site'));
    }
}
