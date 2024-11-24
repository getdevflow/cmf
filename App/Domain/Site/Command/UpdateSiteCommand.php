<?php

declare(strict_types=1);

namespace App\Domain\Site\Command;

use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\ValueObject\UserId;
use Codefy\CommandBus\PropertyCommand;
use DateTimeInterface;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class UpdateSiteCommand extends PropertyCommand
{
    public ?SiteId $siteId = null;

    public ?StringLiteral $siteName = null;

    public ?StringLiteral $siteSlug = null;

    public ?StringLiteral $siteDomain = null;

    public ?StringLiteral $siteMapping = null;

    public ?StringLiteral $sitePath = null;

    public ?UserId $siteOwner = null;

    public ?StringLiteral $siteStatus = null;

    public ?DateTimeInterface $siteModified = null;
}
