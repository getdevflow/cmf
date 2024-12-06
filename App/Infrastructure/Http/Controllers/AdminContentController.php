<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\Content\Command\RemoveFeaturedImageCommand;
use App\Domain\Content\Model\Content;
use App\Domain\Content\ValueObject\ContentId;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Cms\Forms\ContentForm;
use Codefy\CommandBus\Busses\SynchronousCommandBus;
use Codefy\CommandBus\Containers\ContainerFactory;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\CommandBus\Odin;
use Codefy\CommandBus\Resolvers\NativeCommandHandlerResolver;
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
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\cms_delete_content;
use function App\Shared\Helpers\cms_insert_content;
use function App\Shared\Helpers\cms_update_content;
use function App\Shared\Helpers\get_all_content_with_filters;
use function App\Shared\Helpers\get_content_by_id;
use function App\Shared\Helpers\get_content_type_by;
use function Codefy\Framework\Helpers\config;
use function Qubus\Error\Helpers\is_error;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;
use function sprintf;

final class AdminContentController extends BaseController
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
     * @param string $contentTypeSlug
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function contentCreate(ServerRequest $request, string $contentTypeSlug): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'create:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $id = cms_insert_content($request);
            if (is_error($id)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Insertion error occurred.', domain: 'devflow')
                );
            } else {
                Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 201));
            }

            return $this->redirect(admin_url(path: "content-type/{$contentTypeSlug}/{$id}/"));
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
                message: t__(msgid: 'Insertion exception occurred and was logged.', domain: 'devflow')
            );
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Final catch insertion exception occurred and was logged.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeSlug
     * @return ResponseInterface|string|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Exception
     */
    public function contentCreateView(ServerRequest $request, string $contentTypeSlug): ResponseInterface|null|string
    {
        if (false === $this->user->can(permissionName: 'create:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $getContentType = get_content_type_by('slug', $contentTypeSlug);
            if (empty($getContentType->id) || is_false__($getContentType)) {
                return JsonResponseFactory::create(
                    data: t__('The content type does not exist.', domain: 'devflow'),
                    status: 404
                );
            }

            return $this->view->render(
                template: 'framework::backend/admin/content/create',
                data: [
                    'title' => sprintf(esc_html__('Create %s Content', domain: 'devflow'), $getContentType->title),
                    'type' => $getContentType,
                    'request' => $request->getParsedBody(),
                    'form' => (new ContentForm())->buildForm($request->getParsedBody(), $getContentType->slug, null),
                ]
            );
        } catch (
            CommandPropertyNotFoundException |
            UnresolvableQueryHandlerException |
            TypeException |
            ReflectionException |
            \Exception $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
        }

        return JsonResponseFactory::create(
            data: t__('The content type does not exist.', domain: 'devflow'),
            status: 404
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeSlug
     * @param string $contentId
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function contentChange(
        ServerRequest $request,
        string $contentTypeSlug,
        string $contentId
    ): ?ResponseInterface {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $dataArrayMerge = array_merge(['id' => $contentId], $request->getParsedBody());

        try {
            $id = cms_update_content($dataArrayMerge);
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
                message: t__(msgid: 'Change exception occurred and was logged.', domain: 'devflow')
            );
        }

        return $this->redirect(admin_url(path: "content-type/{$contentTypeSlug}/{$contentId}/"));
    }

    /**
     * @param ServerRequest $request
     * @param string $contentId
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
    public function contentView(ServerRequest $request, string $contentId): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            /** @var Content $content */
            $content = get_content_by_id($contentId);

            if (empty($content->id) || is_false__($content)) {
                return JsonResponseFactory::create(
                    data: t__('The content does not exist.', domain: 'devflow'),
                    status: 404
                );
            }

            return $this->view->render(
                template: 'framework::backend/admin/content/view',
                data: [
                    'title' => $content->title,
                    'content' => $content,
                    'type' => get_content_type_by('slug', $content->type),
                    'form' => (new ContentForm())->buildForm($content->toArray(), $content->type, $content->id),
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
            data: t__('The content does not exist.', domain: 'devflow'),
            status: 404
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeSlug
     * @return string|ResponseInterface
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     * @throws \Exception
     */
    public function contentViewByType(ServerRequest $request, string $contentTypeSlug): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $getContentType = get_content_type_by('slug', $contentTypeSlug);
            if (empty($getContentType->id) || is_false__($getContentType)) {
                return JsonResponseFactory::create(
                    data: t__(msgid: 'Content not found.', domain: 'devflow'),
                    status: 404
                );
            }

            /** @var Content $content */
            $content = get_all_content_with_filters($contentTypeSlug);

            return $this->view->render(
                template: 'framework::backend/admin/content/content-by-type',
                data: [
                    'title' => sprintf(esc_html__(string: '%s Content', domain: 'devflow'), $getContentType->title),
                    'contentArray' => $content,
                    'type' => $getContentType->slug
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
            data: t__(msgid: 'The content does not exist.', domain: 'devflow'),
            status: 404
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $contentId
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function removeFeaturedImage(ServerRequest $request, string $contentId): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $resolver = new NativeCommandHandlerResolver(
                container: ContainerFactory::make(config: config('commandbus.container'))
            );
            $odin = new Odin(bus: new SynchronousCommandBus($resolver));

            $command = new RemoveFeaturedImageCommand([
                'contentId'  => ContentId::fromString($contentId),
                'contentFeaturedImage' => new StringLiteral('')
            ]);

            $odin->execute($command);

            Devflow::inst()::$APP->flash->success(
                message: t__(msgid: 'Removal of featured image was successful.', domain: 'devflow')
            );
        } catch (
            CommandCouldNotBeHandledException |
            CommandPropertyNotFoundException |
            TypeException |
            UnresolvableCommandHandlerException |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Removal exception occurred and was logged.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $contentTypeSlug
     * @param string $contentId
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function contentDelete(ServerRequest $request, string $contentTypeSlug, string $contentId): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'delete:content', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $delete = cms_delete_content($contentId);

            if (is_error($delete) || is_false__($delete)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'A deletion error occurred.', domain: 'devflow')
                );
            }

            Devflow::inst()::$APP->flash->success(
                message: t__(msgid: 'Removal was successful.', domain: 'devflow')
            );
        } catch (
            CommandCouldNotBeHandledException |
            ContainerExceptionInterface |
            CommandPropertyNotFoundException |
            InvalidArgumentException |
            NotFoundExceptionInterface |
            UnresolvableCommandHandlerException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'A deletion exception occurred and was logged.', domain: 'devflow')
            );
        }

        return $this->redirect(admin_url("content-type/{$contentTypeSlug}/"));
    }
}
