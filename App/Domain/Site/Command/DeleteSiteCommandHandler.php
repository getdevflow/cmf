<?php

declare(strict_types=1);

namespace App\Domain\Site\Command;

use App\Domain\Site\Repository\SiteRepository;
use App\Domain\Site\Site;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;

final class DeleteSiteCommandHandler implements CommandHandler
{
    public function __construct(public SiteRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(DeleteSiteCommand|Command $command): void
    {
        /** @var Site $site */
        $site = $this->aggregateRepository->loadAggregateRoot($command->siteId);

        $site->changeSiteDeleted($command->siteId);

        $this->aggregateRepository->saveAggregateRoot($site);
    }
}
