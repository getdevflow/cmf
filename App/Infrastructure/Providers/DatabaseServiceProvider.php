<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use App\Shared\Services\Registry;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\EventDispatcher\ActionFilter\Filter;
use ReflectionException;

use function Codefy\Framework\Helpers\base_path;
use function Qubus\Security\Helpers\load_default_textdomain;

class DatabaseServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->codefy->alias(original: Database::class, alias: NativePdoDatabase::class);
        $this->codefy->share(nameOrInstance: Database::class);

        /** @var Database $database */
        $database = $this->codefy->make(Database::class);

        Filter::getInstance()->removeFilter('core_locale', function ($locale) {
            return '';
        });

        Filter::getInstance()->addFilter('core_locale', function ($locale) use ($database) {
            $sql = "SELECT option_value FROM {$database->prefix}option WHERE option_key = 'site_locale' LIMIT 1";
            $locale = $database->getVar($sql);
            return $locale;
        });

        load_default_textdomain(domain: 'devflow', path: base_path('locale/'));

        /** Do not touch. */
        Registry::getInstance()->set('dfdb', $database);
    }
}
