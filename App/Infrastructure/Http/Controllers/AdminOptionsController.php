<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\Site\Command\UpdateSiteCommand;
use App\Domain\Site\Model\Site;
use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\Options;
use App\Infrastructure\Services\UserAuth;
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
use Qubus\Http\ServerRequest;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\cms_unique_site_slug;
use function App\Shared\Helpers\current_user_can;
use function App\Shared\Helpers\get_current_site_key;
use function App\Shared\Helpers\get_site_by;
use function App\Shared\Helpers\get_user_timezone;
use function Codefy\Framework\Helpers\config;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;
use function Qubus\Security\Helpers\unslash;
use function Qubus\Support\Helpers\is_false__;
use function sprintf;

final class AdminOptionsController extends BaseController
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
    public function options(ServerRequest $request): ?\Psr\Http\Message\ResponseInterface
    {
        if (!current_user_can('manage:options')) {
            Devflow::inst()::$APP->flash->{'error'}(
                message: t__(msgid: 'Access denied', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        $options = $request->getParsedBody();

        if (!empty(array_filter($options))) {
            foreach ($options as $option => $value) {
                $option = trim($option);

                if (!is_array($value)) {
                    $value = trim($value);
                }

                $value = unslash($value);
                Options::factory()->update($option, $value);
            }

            Devflow::inst()::$APP->flash->success(
                Devflow::inst()::$APP->flash->notice(200),
            );
        }
        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CommandPropertyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws UnresolvableQueryHandlerException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ReflectionException
     * @throws SessionException
     * @throws TypeException
     */
    public function generalOptions(ServerRequest $request): string|ResponseInterface
    {
        if (!current_user_can('manage:options')) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        $resolver = new NativeCommandHandlerResolver(
            container: ContainerFactory::make(config: config('commandbus.container'))
        );
        $odin = new Odin(bus: new SynchronousCommandBus($resolver));

        $options = [
            'sitename', 'site_description', 'charset', 'admin_email', 'site_locale',
            'cookieexpire', 'cookiepath', 'site_timezone', 'api_key'
        ];
        foreach ($options as $optionName) {
            if (!isset($request->getParsedBody()[$optionName])) {
                continue;
            }

            $value = $request->getParsedBody()[$optionName];
            Options::factory()->update($optionName, $value);
        }

            /** @var Site $currentSite */
        $currentSite = get_site_by('key', get_current_site_key());

        if (!is_false__($currentSite) && $currentSite->name !== $request->getParsedBody()['sitename']) {
            $siteSlug = cms_unique_site_slug(
                $currentSite->slug,
                $request->getParsedBody()['sitename'],
                $currentSite->id
            );

            try {
                $command = new UpdateSiteCommand([
                    'siteId' => SiteId::fromString($currentSite->id),
                    'siteName' => new StringLiteral($request->getParsedBody()['sitename']),
                    'siteSlug' => new StringLiteral($siteSlug),
                    'siteDomain' => new StringLiteral($currentSite->domain),
                    'siteMapping' => new StringLiteral($currentSite->mapping ?? ''),
                    'sitePath' => new StringLiteral($currentSite->path),
                    'siteOwner' => UserId::fromString($currentSite->owner),
                    'siteStatus' => new StringLiteral($currentSite->status),
                    'siteModified' => QubusDateTimeImmutable::now(get_user_timezone()),
                ]);

                $odin->execute($command);
            } catch (
                CommandCouldNotBeHandledException | UnresolvableCommandHandlerException | ReflectionException $e
            ) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'SQLSTATE[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    ),
                    [
                    'Route' => '/admin/general/'
                    ]
                );

                Devflow::inst()::$APP->flash->error(
                    message: esc_html__(
                        string: 'Options not updated.',
                        domain: 'devflow'
                    )
                );
            }
        }

        Devflow::inst()::$APP->flash->success(
            Devflow::inst()::$APP->flash->notice(200),
        );

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
     * @return ResponseInterface|null
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
    public function generalView(): string|ResponseInterface
    {
        if (!current_user_can('manage:options')) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        return $this->view->render(
            template: 'framework::backend/general',
            data: ['title' => t__(msgid: 'General Options', domain: 'devflow')]
        );
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
     * @throws UnresolvableQueryHandlerException
     */
    public function readingOptions(ServerRequest $request): string|ResponseInterface
    {
        if (!current_user_can('manage:options')) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        $options = [
            'content_per_page', 'charset', 'date_format', 'time_format'
        ];

        foreach ($options as $optionName) {
            if (!isset($request->getParsedBody()[$optionName])) {
                continue;
            }

            $value = $request->getParsedBody()[$optionName];
            Options::factory()->update($optionName, $value);
        }

        Devflow::inst()::$APP->flash->{'success'}(
            Devflow::inst()::$APP->flash->notice(num: 200),
        );

        return $this->redirect($request->getServerParams()['HTTP_REFERER']);
    }

    /**
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
     */
    public function readingView(): string|ResponseInterface
    {
        if (!current_user_can('manage:options')) {
            Devflow::inst()::$APP->flash->error(
                message: t__(msgid: 'Access denied', domain: 'devflow'),
            );
            return $this->redirect(admin_url());
        }

        return $this->view->render(
            template: 'framework::backend/reading',
            data: ['title' => t__(msgid: 'Reading Options', domain: 'devflow')]
        );
    }
}
