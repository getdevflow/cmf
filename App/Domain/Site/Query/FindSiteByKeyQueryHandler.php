<?php

declare(strict_types=1);

namespace App\Domain\Site\Query;

use App\Domain\Site\Query\Trait\PopulateSiteQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function is_array;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

class FindSiteByKeyQueryHandler implements QueryHandler
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
     * @throws Exception
     */
    public function handle(FindSiteByKeyQuery|Query $query): array|object
    {
        $sql = "SELECT * FROM {$this->dfdb->basePrefix}site WHERE site_key = ?";

        $data = $this->dfdb->getRow($this->dfdb->prepare($sql, [$query->siteKey->toNative()]), Database::ARRAY_A);

        if (is_null__($data)) {
            return [];
        }

        $content = $this->populate($data);

        if (is_array($content)) {
            $content = convert_array_to_object($content);
        }

        return $content;
    }
}
