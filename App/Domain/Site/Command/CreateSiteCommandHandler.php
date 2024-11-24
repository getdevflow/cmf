<?php

declare(strict_types=1);

namespace App\Domain\Site\Command;

use App\Domain\Site\Repository\SiteRepository;
use App\Domain\Site\Site;
use App\Domain\User\ValueObject\UserId;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Qubus\Exception\Data\TypeException;

final class CreateSiteCommandHandler implements CommandHandler
{
    public function __construct(public SiteRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws TypeException
     */
    public function handle(CreateSiteCommand|Command $command): void
    {
        $site = Site::createSite(
            $command->siteId,
            $command->siteKey,
            $command->siteName,
            $command->siteSlug,
            $command->siteDomain,
            $command->siteMapping,
            $command->sitePath,
            UserId::fromString($command->siteOwner->__toString()),
            $command->siteStatus,
            $command->siteRegistered,
        );

        $this->aggregateRepository->saveAggregateRoot($site);
    }
}
