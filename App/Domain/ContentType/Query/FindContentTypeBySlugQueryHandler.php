<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Query;

use App\Domain\ContentType\Query\Trait\PopulateContentTypeQueryAware;
use App\Infrastructure\Persistence\Database;
use Codefy\QueryBus\Query;
use Codefy\QueryBus\QueryHandler;
use Exception;

use function App\Shared\Helpers\dfdb;
use function is_array;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

class FindContentTypeBySlugQueryHandler implements QueryHandler
{
    use PopulateContentTypeQueryAware;

    protected ?Database $dfdb = null;

    public function __construct(Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @inheritDoc
     * @param FindContentTypeBySlugQuery|Query $query
     * @throws Exception
     */
    public function handle(FindContentTypeBySlugQuery|Query $query): array|null|object
    {
        $sql = "SELECT * FROM {$this->dfdb->prefix}content_type WHERE content_type_slug = ?";

        $data = $this->dfdb->getRow(
            query: $this->dfdb->prepare($sql, [$query->contentTypeSlug->toNative()]),
            output: Database::ARRAY_A
        );

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
