<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Command;

use App\Domain\ContentType\ContentType;
use App\Domain\ContentType\Repository\ContentTypeRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;

class UpdateContentTypeCommandHandler implements CommandHandler
{
    public function __construct(public ContentTypeRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(UpdateContentTypeCommand|Command $command): void
    {
        /** @var ContentType $contentType */
        $contentType = $this->aggregateRepository->loadAggregateRoot($command->contentTypeId);

        $contentType->changeTitle($command->contentTypeTitle);
        $contentType->changeContentTypeSlug($command->contentTypeSlug);
        $contentType->changeContentTypeDescription($command->contentTypeDescription);

        $this->aggregateRepository->saveAggregateRoot($contentType);
    }
}
