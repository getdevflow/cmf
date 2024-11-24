<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Command;

use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\CommandBus\PropertyCommand;

class DeleteContentTypeCommand extends PropertyCommand
{
    public ?ContentTypeId $contentTypeId = null;
}
