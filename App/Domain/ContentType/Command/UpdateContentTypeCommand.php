<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Command;

use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\CommandBus\PropertyCommand;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class UpdateContentTypeCommand extends PropertyCommand
{
    public ?ContentTypeId $contentTypeId = null;

    public ?StringLiteral $contentTypeTitle = null;

    public ?StringLiteral $contentTypeSlug = null;

    public ?StringLiteral $contentTypeDescription = null;
}
