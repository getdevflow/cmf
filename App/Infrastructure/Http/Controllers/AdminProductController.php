<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\Product\Command\RemoveFeaturedImageCommand;
use App\Domain\Product\Model\Product;
use App\Domain\Product\ValueObject\ProductId;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\UserAuth;
use Cms\Forms\ProductForm;
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
use function App\Shared\Helpers\cms_delete_product;
use function App\Shared\Helpers\cms_insert_product;
use function App\Shared\Helpers\cms_update_product;
use function App\Shared\Helpers\get_product_by_id;
use function App\Shared\Helpers\get_products;
use function Codefy\Framework\Helpers\config;
use function Qubus\Error\Helpers\is_error;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_false__;

final class AdminProductController extends BaseController
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
     * @return string|ResponseInterface
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws \Exception
     */
    public function products(ServerRequest $request): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'manage:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        try {
            $products = get_products();

            return $this->view->render(
                template: 'framework::backend/admin/product/index',
                data: [
                    'title' => esc_html__(string: 'Products', domain: 'devflow'),
                    'products' => $products,
                ]
            );
        } catch (UnresolvableQueryHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Query error.', domain: 'devflow')
            );
        }

        return JsonResponseFactory::create(data: t__(msgid: 'Products error', domain: 'devflow'), status: 404);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function productCreate(ServerRequest $request): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'create:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $id = cms_insert_product($request);
            if (is_error($id)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'Insertion error occurred.', domain: 'devflow')
                );
            } else {
                Devflow::inst()::$APP->flash->success(Devflow::inst()::$APP->flash->notice(num: 201));
            }

            return $this->redirect(admin_url(path: "product/{$id}/"));
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
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Insertion exception occurred.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @return ResponseInterface|string|null
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
    public function productCreateView(ServerRequest $request): ResponseInterface|null|string
    {
        if (false === $this->user->can(permissionName: 'create:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        return $this->view->render(
            template: 'framework::backend/admin/product/create',
            data: [
                'title' => t__(msgid: 'Create Product', domain: 'devflow'),
                'request' => $request->getParsedBody(),
                'form' => (new ProductForm())->buildForm($request->getParsedBody(), null),
            ]
        );
    }

    /**
     * @param ServerRequest $request
     * @param string $productId
     * @return ResponseInterface|null
     * @throws CommandCouldNotBeHandledException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     * @throws UnresolvableCommandHandlerException
     */
    public function productChange(
        ServerRequest $request,
        string $productId
    ): ?ResponseInterface {
        if (false === $this->user->can(permissionName: 'update:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $dataArrayMerge = array_merge(['id' => $productId], $request->getParsedBody());

        try {
            $id = cms_update_product($dataArrayMerge);
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

        return $this->redirect(admin_url(path: "product/{$productId}/"));
    }

    /**
     * @param ServerRequest $request
     * @param string $productId
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
    public function productView(ServerRequest $request, string $productId): string|ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            /** @var Product $product */
            $product = get_product_by_id($productId);

            if (empty($product->id) || is_false__($product)) {
                return JsonResponseFactory::create(
                    data: t__('The product does not exist.', domain: 'devflow'),
                    status: 404
                );
            }

            return $this->view->render(
                template: 'framework::backend/admin/product/view',
                data: [
                    'title' => $product->title,
                    'product' => $product,
                    'form' => (new ProductForm())->buildForm($request->getParsedBody(), $product->id),
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

        return JsonResponseFactory::create(data: t__('The product does not exist.', domain: 'devflow'), status: 404);
    }

    /**
     * @param ServerRequest $request
     * @param string $productId
     * @return ResponseInterface|null
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function removeFeaturedImage(ServerRequest $request, string $productId): ?ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'update:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        $resolver = new NativeCommandHandlerResolver(
            container: ContainerFactory::make(config: config('commandbus.container'))
        );
        $odin = new Odin(bus: new SynchronousCommandBus($resolver));

        try {
            $command = new RemoveFeaturedImageCommand([
                'productId'  => ProductId::fromString($productId),
                'productFeaturedImage' => new StringLiteral('')
            ]);

            $odin->execute($command);

            Devflow::inst()::$APP->flash->success(
                message: t__(msgid: 'Removal of featured image was successful.', domain: 'devflow')
            );
        } catch (CommandCouldNotBeHandledException | UnresolvableCommandHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Removal error.', domain: 'devflow')
            );
        }

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @param ServerRequest $request
     * @param string $productId
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function productDelete(ServerRequest $request, string $productId): ResponseInterface
    {
        if (false === $this->user->can(permissionName: 'delete:product', request: $request)) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied.', domain: 'devflow')
            );
            return $this->redirect(admin_url());
        }

        try {
            $delete = cms_delete_product($productId);

            if (is_error($delete) || is_false__($delete)) {
                Devflow::inst()::$APP->flash->error(
                    message: t__(msgid: 'A deletion error occurred.', domain: 'devflow')
                );
            }
        } catch (
            CommandCouldNotBeHandledException |
            CommandPropertyNotFoundException |
            UnresolvableCommandHandlerException |
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'A deletion exception occurred.', domain: 'devflow')
            );
        }

        Devflow::inst()::$APP->flash->success(
            message: t__(msgid: 'Removal was successful.', domain: 'devflow')
        );

        return $this->redirect(admin_url("product/"));
    }
}
