<?php

declare(strict_types=1);

namespace App\Domain\Site\Query;

use App\Domain\Site\Query\Trait\PopulateSiteQueryAware;
use App\Infrastructure\Persistence\Cache\SiteCachePsr16;
use App\Infrastructure\Persistence\Database;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function is_array;
use function md5;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

class FindSiteByIdQueryHandler implements QueryHandler
{
    use PopulateSiteQueryAware;

    protected ?Database $dfdb = null;

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @param FindSiteByIdQuery|Query $query
     * @return array|object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function handle(FindSiteByIdQuery|Query $query): array|object
    {
        $siteId = $query->siteId->toNative();

        $site = null;

        if ('' !== $siteId) {
            if ($data = SimpleCacheObjectCacheFactory::make(namespace: 'sites')->get(md5($siteId))) {
                is_array($data) ? convert_array_to_object($data) : $data;
            }
        }

        $sql = "SELECT * FROM {$this->dfdb->basePrefix}site WHERE site_id = ?";

        if (
            !$data = $this->dfdb->getRow(
                $this->dfdb->prepare(
                    $sql,
                    [$query->siteId->toNative()]
                ),
                Database::ARRAY_A
            )
        ) {
            return [];
        }

        if (!is_null__($data)) {
            $site = $this->populate($data);
            SiteCachePsr16::update($site);
        }

        if (is_array($site)) {
            $site = convert_array_to_object($site);
        }

        return $site;
    }
}
