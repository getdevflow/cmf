<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use App\Domain\Content\Query\Trait\PopulateContentQueryAware;
use App\Infrastructure\Persistence\Database;
use App\Shared\Services\Sanitizer;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use PDOException;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

final class FindContentQueryHandler implements QueryHandler
{
    use PopulateContentQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @throws ReflectionException|Exception
     */
    public function handle(FindContentQuery|Query $query): array
    {
        $contents = [];
        $where = '';

        $sanitizeContentType = Sanitizer::item(item: $query->contentTypeSlug);
        $sanitizeLimit = Sanitizer::item(item: $query->limit, type: 'int', context: '');
        $sanitizeOffset = Sanitizer::item(item: $query->offset, type: 'int', context: '');
        $sanitizeStatus = Sanitizer::item(item: $query->status);

        if (!is_null__($sanitizeContentType)) {
            $prepare = $this->dfdb->prepare(
                "SELECT * FROM {$this->dfdb->prefix}content WHERE content_type = ?",
                [
                    $sanitizeContentType
                ]
            );

            if ($sanitizeStatus !== 'all') {
                $where .= $this->dfdb->prepare(
                    " AND WHERE content_status = ?",
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
                    foreach ($results as $content) {
                        $contents[] = $this->populate($content);
                    }
                }

                return $contents;
            } catch (PDOException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'SQLSTATE[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    ),
                    [
                        'Db Function' => 'get_all_content'
                    ]
                );
            }
        } else {
            if ($sanitizeStatus !== 'all') {
                $where = $this->dfdb->prepare(
                    " WHERE content_status = ?",
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
                    "SELECT * FROM {$this->dfdb->prefix}content{$where}",
                    Database::ARRAY_A
                );

                if (!is_false__($results)) {
                    foreach ($results as $content) {
                        $contents[] = $this->populate($content);
                    }
                }

                return $contents;
            } catch (PDOException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'SQLSTATE[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    ),
                    [
                        'Query Bus' => 'FindContentQuery',
                    ]
                );
            }
        }

        return $contents;
    }
}
