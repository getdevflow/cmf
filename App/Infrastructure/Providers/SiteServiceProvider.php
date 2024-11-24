<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Persistence\Database;
use App\Shared\Services\Registry;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Support\CodefyServiceProvider;
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
        /** @var Database $cdb */
        $cdb = $this->codefy->make(Database::class);

        try {
            $currentSiteKey = $cdb->getRow(
                $cdb->prepare(
                    "SELECT site_key FROM {$cdb->basePrefix}site WHERE site_domain = ? OR site_mapping = ? LIMIT 1",
                    [
                        $request->getHeaderLine('Host'),
                        $request->getHeaderLine('Host'),
                    ]
                )
            );

            if (null === $currentSiteKey) {
                $default = $this->codefy->configContainer->getConfigKey(key: 'database.default');
                $siteKey = $this->codefy->configContainer->getConfigKey(key: "database.connections.{$default}.prefix");
            } else {
                $siteKey = esc_html($currentSiteKey->site_key);
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
