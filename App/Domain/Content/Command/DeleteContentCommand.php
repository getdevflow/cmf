<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\CommandBus\PropertyCommand;

class DeleteContentCommand extends PropertyCommand
{
    public ?ContentId $contentId = null;
}
