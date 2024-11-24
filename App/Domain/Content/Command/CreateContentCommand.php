<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\ValueObject\ContentId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\CommandBus\PropertyCommand;
use DateTimeInterface;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class CreateContentCommand extends PropertyCommand
{
    public ?ContentId $contentId = null;
    public ?StringLiteral $contentTitle = null;

    public ?StringLiteral $contentSlug = null;

    public ?StringLiteral $contentBody = null;

    public ?UserId $contentAuthor = null;

    public ?StringLiteral $contentTypeSlug = null;

    public ?ContentId $contentParent = null;

    public ?IntegerNumber $contentSidebar = null;

    public ?IntegerNumber $contentShowInMenu = null;

    public ?IntegerNumber $contentShowInSearch = null;

    public ?StringLiteral $contentFeaturedImage = null;

    public ?ArrayLiteral $meta = null;

    public ?StringLiteral $contentStatus = null;

    public ?DateTimeInterface $contentCreated = null;

    public ?DateTimeInterface $contentCreatedGmt = null;

    public ?DateTimeInterface $contentPublished = null;

    public ?DateTimeInterface $contentPublishedGmt = null;
}
