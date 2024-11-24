<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindContentByIdQuery extends PropertyCommand implements Query
{
    public ?ContentId $contentId = null;
}
