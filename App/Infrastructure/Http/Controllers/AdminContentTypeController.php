<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\ContentType\Model\ContentType;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Http\BaseController;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
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
use function App\Shared\Helpers\cms_delete_content_type;
use function App\Shared\Helpers\cms_insert_content_type;
use function App\Shared\Helpers\cms_update_content_type;
use function App\Shared\Helpers\get_all_content_types;
use function App\Shared\Helpers\get_content_type_by;
use function Qubus\Error\Helpers\is_error;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;

final class AdminContentTypeController extends BaseController
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
     * @throws Exception
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function contentTypeCreate(ServerRequest $request): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'create:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $id = cms_insert_content_type($request);
            if (is_error($id)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Insertion error occurred.', domain: 'devflow')
                );
            }

            Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 201));
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
    public function contentTypes(ServerRequest $request): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'manage:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            /** @var ContentType[] $contentTypes */
            $contentTypes = get_all_content_types();

            return $this->view->render(
                template: 'framework::backend/admin/content-type/content-type',
                data: [
                    'title' => 'Content Types',
                    'types' => $contentTypes,
                    'request' => $request->getParsedBody(),
                ]
            );
        } catch (UnresolvableQueryHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Error fetching content types.', domain: 'devflow')
            );
        }

        return JsonResponseFactory::create(data: t__(msgid: 'Content types error', domain: 'devflow'), status: 404);
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeId
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function contentTypeChange(ServerRequest $request, string $contentTypeId): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $dataArrayMerge = array_merge(['id' => $contentTypeId], $request->getParsedBody());

        try {
            $id = cms_update_content_type($dataArrayMerge);
            if (is_error($id)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Change error occurred.', domain: 'devflow')
                );
            }

            Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 200));
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
                message: t__(msgid: 'Change exception occurred.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeId
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
    public function contentTypeView(ServerRequest $request, string $contentTypeId): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            /** @var ContentType $contentType */
            $contentType = get_content_type_by('id', $contentTypeId);

            if (empty($contentType->id)) {
                return JsonResponseFactory::create(
                    data: t__(msgid: 'The content type does not exist.', domain: 'devflow'),
                    status: 404
                );
            }

            if (is_false__($contentType)) {
                return JsonResponseFactory::create(
                    data: t__(msgid: 'The content type does not exist.', domain: 'devflow'),
                    status: 404
                );
            }

            return $this->view->render(
                template: 'framework::backend/admin/content-type/update-content-type',
                data: [
                    'title' => $contentType->title,
                    'type' => $contentType,
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
            data: t__(msgid: 'The content type does not exist.', domain: 'devflow'),
            status: 404
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeId
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function contentTypeDelete(ServerRequest $request, string $contentTypeId): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'delete:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $delete = cms_delete_content_type($contentTypeId);

            if (is_error($delete) || is_false__($delete)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Delete error occurred.', domain: 'devflow')
                );
            } else {
                Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 200));
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
                message: t__(msgid: 'Delete exception occurred.', domain: 'devflow')
            );
        }

        return $this->redirect(admin_url('content-type'));
    }
}
