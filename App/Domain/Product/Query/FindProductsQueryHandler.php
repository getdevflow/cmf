<?php

declare(strict_types=1);

namespace App\Domain\Product\Query;

use App\Domain\Product\Query\Trait\PopulateProductQueryAware;
use App\Infrastructure\Persistence\Database;
use App\Shared\Services\Sanitizer;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

final class FindProductsQueryHandler implements QueryHandler
{
    use PopulateProductQueryAware;

    protected ?Database $dfdb = null;

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @throws ReflectionException|Exception
     */
    public function handle(FindProductsQuery|Query $query): array
    {
        $products = [];
        $where = '';

        $sanitizeProductSku = Sanitizer::item(item: $query->productSku);
        $sanitizeLimit = Sanitizer::item(item: $query->limit, type: 'int', context: '');
        $sanitizeOffset = Sanitizer::item(item: $query->offset, type: 'int', context: '');
        $sanitizeStatus = Sanitizer::item(item: $query->status);

        if (!is_null__($sanitizeProductSku)) {
            $prepare = $this->dfdb->prepare(
                "SELECT * FROM {$this->dfdb->prefix}product WHERE product_sku = ?",
                [
                    $sanitizeProductSku
                ]
            );

            if ($sanitizeStatus !== 'all') {
                $where .= $this->dfdb->prepare(
                    " AND WHERE product_status = ?",
                    [
                        $sanitizeStatus
                    ]
                );
            }

            if ($sanitizeLimit > 0 && !is_null__($sanitizeOffset)) {
                $where .= sprintf(" LIMIT %d OFFSET %d", $sanitizeLimit, $sanitizeOffset);
            } elseif ($sanitizeLimit > 0 && is_null__($sanitizeOffset)) {
                $where .= sprintf(" LIMIT %d", $sanitizeLimit);
            } elseif ($sanitizeLimit <= 0 && !is_null__($sanitizeOffset)) {
                $where .= sprintf(" OFFSET %d", $sanitizeOffset);
            }

            try {
                $results = $this->dfdb->getResults(query: $prepare . $where, output: Database::ARRAY_A);

                if (!is_false__($results)) {
                    foreach ($results as $product) {
                        $products[] = $this->populate($product);
                    }
                }

                return $products;
            } catch (PDOException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'SQLSTATE[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    ),
                    [
                        'Db Function' => 'get_all_products'
                    ]
                );
            }
        } else {
            if ($sanitizeStatus !== 'all') {
                $where = $this->dfdb->prepare(
                    " WHERE product_status = ?",
                    [
                        $sanitizeStatus
                    ]
                );
            }

            if ($sanitizeLimit > 0 && !is_null__($sanitizeOffset)) {
                $where .= sprintf(" LIMIT %d OFFSET %d", $sanitizeLimit, $sanitizeOffset);
            } elseif ($sanitizeLimit > 0 && is_null__($sanitizeOffset)) {
                $where .= sprintf(" LIMIT %d", $sanitizeLimit);
            } elseif ($sanitizeLimit <= 0 && !is_null__($sanitizeOffset)) {
                $where .= sprintf(" OFFSET %d", $sanitizeOffset);
            }

            try {
                $results = $this->dfdb->getResults(
                    "SELECT * FROM {$this->dfdb->prefix}product{$where}",
                    Database::ARRAY_A
                );

                if (!is_false__($results)) {
                    foreach ($results as $product) {
                        $products[] = $this->populate($product);
                    }
                }

                return $products;
            } catch (PDOException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'SQLSTATE[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    ),
                    [
                        'Query Bus' => 'FindProductsQuery',
                    ]
                );
            }
        }

        return $products;
    }
}
