<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use App\Domain\Content\Query\Trait\PopulateContentQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Exception;

use function App\Shared\Helpers\dfdb;
use function is_array;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

final class FindContentByTypeQueryHandler implements QueryHandler
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
    public function handle(FindContentByTypeQuery|Query $query): array|object
    {
        $sql = "SELECT * FROM {$this->dfdb->prefix}content WHERE content_type = ?";

        $data = $this->dfdb->getRow($this->dfdb->prepare($sql, [$query->contentType->toNative()]), Database::ARRAY_A);

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
