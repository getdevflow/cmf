<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Shared\Services\Registry;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Support\CodefyServiceProvider;
use PDO;
use PDOException;
use Psr\Http\Message\RequestInterface;
use Qubus\Exception\Exception;
use ReflectionException;

use function Qubus\Security\Helpers\esc_html;
use function sprintf;

final class SiteServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        $this->registerSiteKey();
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function registerSiteKey(): void
    {
        /** @var RequestInterface $request */
        $request = $this->codefy->make(RequestInterface::class);

        /** @var PDO $pdo */
        $pdo = $this->codefy->make(PDO::class);

        $default = $this->codefy->configContainer->getConfigKey(key: 'database.default');
        $prefix = $this->codefy->configContainer->getConfigKey(key: "database.connections.{$default}.prefix");

        try {
            $sql = "SELECT site_key FROM {$prefix}site WHERE site_domain = :domain OR site_mapping = :mapping LIMIT 1";
            $sth = $pdo->prepare($sql);
            $sth->execute(['domain' => $request->getHeaderLine('Host'), 'mapping' => $request->getHeaderLine('Host')]);

            $currentSiteKey = $sth->fetchColumn();

            if (false === $currentSiteKey) {
                $siteKey = $prefix;
            } else {
                $siteKey = esc_html($currentSiteKey);
            }
            /**
             * Set site key.
             */
            Registry::getInstance()->set('siteKey', $siteKey);
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(
                sprintf('CURRENT_SITEKEY[%s]: %s', $ex->getCode(), $ex->getMessage())
            );
        }
    }
}
