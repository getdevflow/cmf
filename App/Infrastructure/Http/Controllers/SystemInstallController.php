<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Devflow;
use App\Domain\Site\Command\CreateSiteCommand;
use App\Domain\Site\Model\Site;
use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserToken;
use App\Infrastructure\Persistence\Database;
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
use Codefy\Framework\Support\Password;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionException;
use Qubus\Http\Session\SessionService;
use Qubus\Routing\Router;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\Support\Inflector;
use Qubus\ValueObjects\Identity\Ulid;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\View\Renderer;
use ReflectionException;

use function App\Shared\Helpers\admin_url;
use function App\Shared\Helpers\cms_insert_user;
use function App\Shared\Helpers\generate_random_password;
use function App\Shared\Helpers\generate_random_username;
use function App\Shared\Helpers\generate_unique_key;
use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\storage_path;
use function file_put_contents;
use function Qubus\Security\Helpers\t__;
use function sprintf;

use const LOCK_EX;

final class SystemInstallController extends BaseController
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

    public function install(): mixed
    {
        return $this->view->render(template: 'framework::backend/install', data: ['title' => 'Devflow Installer']);
    }

    /**
     * @throws ReflectionException
     * @throws SessionException
     */
    public function installAction(): void
    {
        try {
            $data = $this->createSuperAdmin();
            $this->createMainSite($data['id']);
            $this->createSiteOptions();

            Devflow::inst()::$APP->flash->success(
                sprintf(
                    t__(msgid: 'Please copy and save your login details: %s : %s', domain: 'devflow'),
                    $data['login'],
                    $data['pass']
                )
            );

            file_put_contents(storage_path(path: 'install.lock'), LOCK_EX);

            $this->redirect(admin_url('admin/login/'));
        } catch (
            TypeException |
            Exception |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error(message: $e->getMessage());
            Devflow::inst()::$APP->flash->error(t__(msgid: 'Installation error occurred.', domain: 'devflow'));
        } catch (\Exception $e) {
            FileLoggerFactory::getLogger()->error(message: $e->getMessage());
        }
    }

    /**
     * @throws ReflectionException
     * @throws TypeException
     * @throws SessionException
     * @throws Exception
     */
    private function createSuperAdmin(): array
    {
        $resolver = new NativeCommandHandlerResolver(
            container: ContainerFactory::make(config: config(key: 'commandbus.container'))
        );
        $odin = new Odin(bus: new SynchronousCommandBus($resolver));

        $login = generate_random_username();
        $password = generate_random_password(26);

        $user = new User();
        //$user->id = UserId::generateAsString();
        $user->fname = 'Super';
        $user->mname = '';
        $user->lname = 'Admin';
        $user->email = 'admin@devflow.com';
        $user->login = $login;
        $user->token = UserToken::generateAsString();
        $user->role = 'super';
        $user->pass = Password::hash($password);
        $user->url = '';
        $user->bio = '';
        $user->status = 'A';
        $user->timezone = 'UTC';
        $user->dateFormat = 'd F Y';
        $user->timeFormat = 'h:i A';
        $user->locale = 'en';
        $user->registered = QubusDateTimeImmutable::now(config(key: 'app.timezone'))->format('Y-m-d H:i:s');

        $meta = [
            'username' => $login,
            'email' => $user->email,
            'fname' => $user->fname,
            'mname' => $user->mname,
            'lname' => $user->lname,
            'bio' => '',
            'timezone' => $user->timezone,
            'date_format' => $user->dateFormat,
            'time_format' => $user->timeFormat,
            'locale' => $user->locale,
            'status' => 'A',
            'admin_layout' => 0,
            'admin_sidebar' => 0,
            'admin_skin' => 'skin-purple-light'
        ];

        try {
            /*$command = new CreateUserCommand([
                'id' => UserId::fromString($user->id),
                'fname' => new StringLiteral($user->fname),
                'mname' => new StringLiteral($user->mname),
                'lname' => new StringLiteral($user->lname),
                'email' => new EmailAddress($user->email),
                'login' => Username::fromString($user->login),
                'token' => UserToken::fromString($user->token),
                'pass' => new StringLiteral($user->pass),
                'url' => new StringLiteral($user->url),
                'timezone' => new StringLiteral($user->timezone),
                'dateFormat' => new StringLiteral($user->dateFormat),
                'timeFormat' => new StringLiteral($user->timeFormat),
                'locale' => new StringLiteral($user->locale),
                'registered' => QubusDateTimeImmutable::now(config(key: 'app.timezone')),
                'usermeta' => $meta,
            ]);

            $odin->execute($command);

            foreach ($meta as $key => $value) {
                update_user_option($user->id, $key, $value);
            }

            $user->setRole(role: 'super');*/

            $userId = cms_insert_user($user);
        } catch (
            CommandPropertyNotFoundException |
            ReflectionException |
            UnresolvableQueryHandlerException |
            NotFoundExceptionInterface |
            ContainerExceptionInterface |
            TypeException |
            Exception $e
        ) {
            Devflow::inst()::$APP->flash->error(
                message: t__(
                    msgid: 'Installation not complete error.',
                    domain: 'devflow'
                )
            );
            $this->redirect(admin_url(path: 'admin/install/'));
        }

        return [
            'id' => $userId,
            'login' => $login,
            'pass' => $password,
        ];
    }

    /**
     * @throws \Exception
     */
    private function createSiteOptions(): void
    {
        $options = [
            'sitename' => config(key: 'app.name'),
            'site_description' => 'Just another Devflow site.',
            'admin_email' => 'admin@example.com',
            'site_locale' => config(key: 'app.locale'),
            'cookieexpire' => 604800,
            'cookiepath' => '/',
            'site_timezone' => config(key: 'app.timezone'),
            'admin_skin' => 'skin-purple-light',
            'date_format' => 'd F Y',
            'time_format' => 'h:i A',
            'content_per_page' => 6,
            'api_key' => generate_unique_key(length: 32),
        ];

        $this->dfdb->qb()->transactional(function () use ($options) {
            foreach ($options as $optionName => $optionValue) {
                $this->dfdb->qb()->table($this->dfdb->basePrefix . 'option')
                ->set([
                    'option_id' => Ulid::generateAsString(),
                    'option_key' => $optionName,
                    'option_value' => $optionValue,
                ])
                ->save();
            }
        });
    }

    /**
     * @param string $userId
     * @throws Exception
     * @throws ReflectionException
     * @throws SessionException
     */
    private function createMainSite(string $userId): void
    {
        $resolver = new NativeCommandHandlerResolver(
            container: ContainerFactory::make(config: config(key: 'commandbus.container'))
        );
        $odin = new Odin(bus: new SynchronousCommandBus($resolver));

        $dbConnection = config(key: 'database.default');

        try {
            $site = new Site();
            $site->key = config(key: "database.connections.{$dbConnection}.prefix");
            $site->name = config(key: 'app.name');
            $site->slug = Inflector::slugify(config(key: 'app.name'));
            $site->domain = config(key: 'cms.main_site_url');
            $site->path = config(key: 'cms.main_site_path');
            $site->owner = $userId;
            $site->status = 'public';

            $command = new CreateSiteCommand([
                'siteId' => new SiteId(),
                'siteKey' => new StringLiteral($site->key),
                'siteName' => new StringLiteral($site->name),
                'siteSlug' => new StringLiteral($site->slug),
                'siteDomain' => new StringLiteral($site->domain),
                'sitePath' => new StringLiteral($site->path),
                'siteOwner' => UserId::fromString($site->owner),
                'siteStatus' => new StringLiteral($site->status),
                'siteRegistered' => QubusDateTimeImmutable::now(config(key: 'app.timezone')),
            ]);

            $odin->execute($command);
        } catch (
            CommandCouldNotBeHandledException |
            UnresolvableCommandHandlerException |
            ReflectionException |
            CommandPropertyNotFoundException |
            TypeException $e
        ) {
            Devflow::inst()::$APP->flash->error(
                message: t__(
                    msgid: 'Installation not complete error.',
                    domain: 'devflow'
                )
            );
            FileLoggerFactory::getLogger()->error(message: $e->getMessage());
            $this->redirect(admin_url(path: 'admin/install/'));
        }
    }
}
