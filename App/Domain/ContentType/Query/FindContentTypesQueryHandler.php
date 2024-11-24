<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Query;

use App\Domain\ContentType\Query\Trait\PopulateContentTypeQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;

use function App\Shared\Helpers\dfdb;
use function Qubus\Support\Helpers\is_null__;

class FindContentTypesQueryHandler implements QueryHandler
{
    use PopulateContentTypeQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @param FindContentTypesQuery|Query $query
     * @return array
     */
    public function handle(FindContentTypesQuery|Query $query): array
    {
        $sql = "SELECT * FROM {$this->dfdb->prefix}content_type";

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
