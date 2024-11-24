<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Query;

use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

class FindContentTypeByIdQuery extends PropertyCommand implements Query
{
    public ?ContentTypeId $contentTypeId = null;
}
