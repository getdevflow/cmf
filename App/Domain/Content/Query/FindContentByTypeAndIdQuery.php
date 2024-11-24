<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class FindContentByTypeAndIdQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $contentTypeSlug = null;

    public ?ContentId $contentId = null;
}
