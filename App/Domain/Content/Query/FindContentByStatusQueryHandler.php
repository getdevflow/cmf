<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use App\Domain\Content\Query\Trait\PopulateContentQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Exception;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_null__;

final class FindContentByStatusQueryHandler implements QueryHandler
{
    use PopulateContentQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function handle(FindContentByStatusQuery|Query $query): array
    {
        $sql = "SELECT * FROM {$this->dfdb->prefix}content WHERE content_status = ?";

        $data = $this->dfdb->getResults(
            query: $this->dfdb->prepare(query: $sql, params: [$query->contentStatus->toNative()]),
            output: Database::ARRAY_A
        );

        $contents = [];

        if (!is_null__($data)) {
            foreach ($data as $content) {
                $contents[] = $this->populate($content);
            }
        }

        return $contents;
    }
}
