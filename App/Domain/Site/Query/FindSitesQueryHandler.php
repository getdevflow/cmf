<?php

declare(strict_types=1);

namespace App\Domain\Site\Query;

use App\Domain\Site\Query\Trait\PopulateSiteQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_null__;

class FindSitesQueryHandler implements QueryHandler
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

    public function handle(FindSitesQuery|Query $query): array
    {
        $sql = "SELECT * FROM {$this->dfdb->basePrefix}site";

        $data = $this->dfdb->getResults(query: $sql, output: Database::ARRAY_A);

        $contents = [];

        if (!is_null__($data)) {
            foreach ($data as $content) {
                $contents[] = $this->populate($content);
            }
        }

        return $contents;
    }
}
