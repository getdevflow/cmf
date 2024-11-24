<?php

declare(strict_types=1);

namespace App\Domain\Site\Command;

use App\Domain\Site\Repository\SiteRepository;
use App\Domain\Site\Site;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;

final class UpdateSiteCommandHandler implements CommandHandler
{
    public function __construct(public SiteRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(UpdateSiteCommand|Command $command): void
    {
        /** @var Site $site */
        $site = $this->aggregateRepository->loadAggregateRoot($command->siteId);

        $site->changeSiteName($command->siteName);
        $site->changeSiteSlug($command->siteSlug);
        $site->changeSiteDomain($command->siteDomain);
        $site->changeSiteMapping($command->siteMapping);
        $site->changeSitePath($command->sitePath);
        $site->changeSiteOwner($command->siteOwner);
        $site->changeSiteStatus($command->siteStatus);
        $site->changeSiteModified($command->siteModified);

        $this->aggregateRepository->saveAggregateRoot($site);
    }
}
