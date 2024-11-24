<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\CommandBus\PropertyCommand;

class RemoveContentParentCommand extends PropertyCommand
{
    public ?ContentId $contentId = null;

    public ?ContentId $contentParent = null;
}
