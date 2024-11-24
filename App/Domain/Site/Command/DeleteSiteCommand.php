<?php

declare(strict_types=1);

namespace App\Domain\Site\Command;

use App\Domain\Site\ValueObject\SiteId;
use Codefy\CommandBus\PropertyCommand;

final class DeleteSiteCommand extends PropertyCommand
{
    public ?SiteId $siteId = null;
}
