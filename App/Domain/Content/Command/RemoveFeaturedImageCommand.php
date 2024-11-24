<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\CommandBus\PropertyCommand;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class RemoveFeaturedImageCommand extends PropertyCommand
{
    public ?ContentId $contentId = null;

    public ?StringLiteral $contentFeaturedImage = null;
}
