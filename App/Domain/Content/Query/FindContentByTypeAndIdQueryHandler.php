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

final class FindContentByTypeAndIdQueryHandler implements QueryHandler
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
    public function handle(FindContentByTypeAndIdQuery|Query $query): array|object
    {
        $sql = "SELECT * FROM {$this->dfdb->prefix}content WHERE content_type = ? AND content_id = ?";

        $data = $this->dfdb->getRow(
            $this->dfdb->prepare(
                $sql,
                [
                    $query->contentTypeSlug->toNative(),
                    $query->contentId->toNative()
                ]
            ),
            Database::ARRAY_A
        );

        if (is_null__(var: $data)) {
            return [];
        }

        $content = $this->populate(data: $data);

        if (is_array(value: $content)) {
            $content = convert_array_to_object(array: $content);
        }

        return $content;
    }
}
