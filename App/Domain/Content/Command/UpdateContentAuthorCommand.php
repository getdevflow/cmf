<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\ValueObject\ContentId;
use App\Domain\User\ValueObject\UserId;
use Codefy\CommandBus\PropertyCommand;
use DateTimeInterface;

class UpdateContentAuthorCommand extends PropertyCommand
{
    public ?ContentId $contentId = null;

    public ?UserId $contentAuthor = null;

    public ?DateTimeInterface $contentModified = null;

    public ?DateTimeInterface $contentModifiedGmt = null;
}
