<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindContentQuery extends PropertyCommand implements Query
{
    public ?string $contentTypeSlug = null;

    public ?int $limit = 0;

    public ?int $offset = null;

    public string $status = 'all';
}
