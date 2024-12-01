<?php

declare(strict_types=1);

namespace App\Domain\Site\Query;

use App\Domain\Site\Query\Trait\PopulateSiteQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function is_array;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

class FindSitesByOwnerQueryHandler implements QueryHandler
{
    use PopulateSiteQueryAware;

    protected ?Database $dfdb = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @throws Exception|ReflectionException
     */
    public function handle(FindSitesByOwnerQuery|Query $query): array|object
    {
        $sql = "SELECT * FROM {$this->dfdb->basePrefix}site WHERE site_owner = ?";

        $data = $this->dfdb->getResults($this->dfdb->prepare($sql, [$query->siteOwner->toNative()]), Database::ARRAY_A);

        $contents = [];

        if (!is_null__($data)) {
            foreach ($data as $content) {
                $contents[] = $this->populate($content);
            }
        }

        return convert_array_to_object($contents);
    }
}
