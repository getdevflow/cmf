<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use App\Domain\Site\Command\CreateSiteCommand;
use App\Domain\Site\Model\Site;
use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserToken;
use App\Infrastructure\Persistence\Database;
use Codefy\CommandBus\Busses\SynchronousCommandBus;
use Codefy\CommandBus\Containers\ContainerFactory;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\CommandBus\Odin;
use Codefy\CommandBus\Resolvers\NativeCommandHandlerResolver;
use Codefy\Framework\Application;
use Codefy\Framework\Console\ConsoleCommand;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Error\Error;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\Support\Inflector;
use Qubus\ValueObjects\Identity\Ulid;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use ReflectionException;

use function App\Shared\Helpers\cms_insert_user;
use function App\Shared\Helpers\generate_random_password;
use function App\Shared\Helpers\generate_random_username;
use function App\Shared\Helpers\generate_unique_key;
use function Codefy\Framework\Helpers\storage_path;
use function file_put_contents;
use function Qubus\Error\Helpers\is_error;
use function sprintf;

use const LOCK_EX;

class InstallCmsCommand extends ConsoleCommand
{
    protected string $name = 'cms:install';

    protected string $description = 'Installs the CMS.';

    public function __construct(protected Application $codefy)
    {
        parent::__construct(codefy: $codefy);
    }

    /**
     * @return int
     * @throws ReflectionException
     * @throws UnresolvableQueryHandlerException|Exception
     */
    public function handle(): int
    {
        if ($this->usersExist()) {
            $this->terminalRaw(string: '<error>Error: system already installed.</error>');
            return ConsoleCommand::FAILURE;
        }

        $this->terminalRaw(string: '<info>Installer started....</info>');

        $user = $this->createSuperAdmin();

        $this->terminalRaw(string: '<info>Creating the super administrator...</info>');

        if (is_error($user)) {
            $this->terminalRaw(string: '<error>Error: not installed.</error>');
            return ConsoleCommand::FAILURE;
        }

        $this->terminalRaw(string: '<info>Creating the main site...</info>');

        $this->createMainSite($user['id']);

        $this->terminalRaw(string: '<info>Adding default site options...</info>');

        $this->createSiteOptions();

        file_put_contents(storage_path(path: 'install.lock'), LOCK_EX);

        $this->terminalRaw(string: '<info>New account details: </info>');
        $this->terminalRaw(string: '<info>=====================</info>');

        $this->terminalRaw(string: sprintf(
            'Username: <comment>%s</comment>',
            $user['login']
        ));

        $this->terminalRaw(string: sprintf(
            'Password: <comment>%s</comment>',
            $user['pass']
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    protected function createSuperAdmin(): array|Error
    {
        $login = generate_random_username();
        $password = generate_random_password(26);

        $user = new User();
        $user->fname = 'Super';
        $user->mname = '';
        $user->lname = 'Admin';
        $user->email = 'admin@devflow.com';
        $user->login = $login;
        $user->token = UserToken::generateAsString();
        $user->role = 'super';
        $user->pass = $password;
        $user->url = '';
        $user->bio = '';
        $user->status = 'A';
        $user->timezone = $this->codefy->configContainer->getConfigKey(key: 'app.timezone');
        $user->dateFormat = 'd F Y';
        $user->timeFormat = 'h:i A';
        $user->locale = $this->codefy->configContainer->getConfigKey(key: 'app.locale');
        $user->registered = QubusDateTimeImmutable::now(
            $this->codefy->configContainer->getConfigKey(key: 'app.timezone')
        )->format('Y-m-d H:i:s');

        try {
            $userId = cms_insert_user($user);

            return [
                'id' => $userId,
                'login' => $login,
                'pass' => $password,
            ];
        } catch (
            CommandPropertyNotFoundException |
            ReflectionException |
            UnresolvableQueryHandlerException |
            NotFoundExceptionInterface |
            ContainerExceptionInterface |
            TypeException |
            Exception $e
        ) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
        }

        return [];
    }

    /**
     * @throws Exception
     */
    protected function createSiteOptions(): void
    {
        $dfdb = $this->codefy->make(Database::class);

        $options = [
            'sitename' => $this->codefy->configContainer->getConfigKey(key: 'app.name'),
            'site_description' => 'Just another Devflow site.',
            'charset' => 'UTF-8',
            'admin_email' => 'admin@example.com',
            'site_locale' => $this->codefy->configContainer->getConfigKey(key: 'app.locale'),
            'cookieexpire' => 604800,
            'cookiepath' => '/',
            'site_timezone' => $this->codefy->configContainer->getConfigKey(key: 'app.timezone'),
            'admin_skin' => 'skin-red',
            'date_format' => 'd F Y',
            'time_format' => 'h:i A',
            'content_per_page' => 6,
            'api_key' => generate_unique_key(length: 32),
        ];

        $dfdb->qb()->transactional(function () use ($dfdb, $options) {
            foreach ($options as $optionName => $optionValue) {
                $dfdb->qb()->table($dfdb->basePrefix . 'option')
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
     * @throws ReflectionException
     * @throws Exception
     */
    protected function createMainSite(string $ownerId): void
    {
        $resolver = new NativeCommandHandlerResolver(
            container: ContainerFactory::make(
                config: $this->codefy->configContainer->getConfigKey(key: 'commandbus.container')
            )
        );
        $odin = new Odin(bus: new SynchronousCommandBus($resolver));

        $dbConnection = $this->codefy->configContainer->getConfigKey(key: 'database.default');

        try {
            $site = new Site();
            $site->key = $this->codefy->configContainer->getConfigKey(
                key: "database.connections.{$dbConnection}.prefix"
            );
            $site->name = $this->codefy->configContainer->getConfigKey(key: 'app.name');
            $site->slug = Inflector::slugify($this->codefy->configContainer->getConfigKey(key: 'app.name'));
            $site->domain = $this->codefy->configContainer->getConfigKey(key: 'cms.main_site_url');
            $site->mapping = '';
            $site->path = $this->codefy->configContainer->getConfigKey(key: 'cms.main_site_path');
            $site->owner = $ownerId;
            $site->status = 'public';

            $siteId = new SiteId();

            $command = new CreateSiteCommand([
                'siteId' => $siteId,
                'siteKey' => new StringLiteral($site->key),
                'siteName' => new StringLiteral($site->name),
                'siteSlug' => new StringLiteral($site->slug),
                'siteDomain' => new StringLiteral($site->domain),
                'siteMapping' => new StringLiteral($site->mapping),
                'sitePath' => new StringLiteral($site->path),
                'siteOwner' => UserId::fromString($site->owner),
                'siteStatus' => new StringLiteral($site->status),
                'siteRegistered' => QubusDateTimeImmutable::now(
                    $this->codefy->configContainer->getConfigKey(key: 'app.timezone')
                ),
            ]);

            $odin->execute($command);

            /**
             * Fires immediately after a new site is saved.
             *
             * @file App/Shared/Helpers/site.php
             * @param string $siteId Site ID.
             * @param Site $site     Site object.
             * @param bool $update   Whether this is an existing site or a new site.
             */
            Action::getInstance()->doAction('save_site', $siteId->toNative(), $site, false);
        } catch (
            CommandCouldNotBeHandledException |
            UnresolvableCommandHandlerException |
            ReflectionException |
            CommandPropertyNotFoundException |
            TypeException $e
        ) {
            FileLoggerFactory::getLogger()->error(message: $e->getMessage());
        }
    }

    /**
     * @throws ReflectionException
     * @throws UnresolvableQueryHandlerException
     */
    protected function usersExist(): bool
    {
        $cdb = $this->codefy->make(name: Database::class);
        $sql = "SELECT * FROM {$cdb->basePrefix}user";
        $users = $cdb->getResults($sql);

        return count($users) > 0 && file_exists(storage_path('install.lock'));
    }
}
