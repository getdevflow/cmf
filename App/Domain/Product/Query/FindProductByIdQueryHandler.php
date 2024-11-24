<?php

declare(strict_types=1);

namespace App\Domain\Product\Query;

use App\Domain\Product\Query\Trait\PopulateProductQueryAware;
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

final class FindProductByIdQueryHandler implements QueryHandler
{
    use PopulateProductQueryAware;

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
    public function handle(FindProductByIdQuery|Query $query): array|object
    {
        $sql = "SELECT * FROM {$this->dfdb->prefix}product WHERE product_id = ?";

        $data = $this->dfdb->getRow($this->dfdb->prepare($sql, [$query->productId->toNative()]), Database::ARRAY_A);

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
