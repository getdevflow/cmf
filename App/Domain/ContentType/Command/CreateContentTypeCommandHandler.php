<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Command;

use App\Domain\ContentType\ContentType;
use App\Domain\ContentType\Repository\ContentTypeRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;

class CreateContentTypeCommandHandler implements CommandHandler
{
    public function __construct(public ContentTypeRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(CreateContentTypeCommand|Command $command): void
    {
        $contentType = ContentType::createContentType(
            contentTypeId: $command->contentTypeId,
            contentTypeTitle: $command->contentTypeTitle,
            contentTypeSlug: $command->contentTypeSlug,
            contentTypeDescription: $command->contentTypeDescription,
        );

        $this->aggregateRepository->saveAggregateRoot($contentType);
    }
}
