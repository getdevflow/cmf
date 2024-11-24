<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\Content;
use App\Domain\Content\Repository\ContentRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;

class CreateContentCommandHandler implements CommandHandler
{
    public function __construct(public ContentRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(CreateContentCommand|Command $command): void
    {
        $content = Content::createContent(
            contentId: $command->contentId,
            contentTitle: $command->contentTitle,
            contentSlug: $command->contentSlug,
            contentBody: $command->contentBody,
            contentAuthor: $command->contentAuthor,
            contentTypeSlug: $command->contentTypeSlug,
            contentSidebar: $command->contentSidebar,
            contentShowInMenu: $command->contentShowInMenu,
            contentShowInSearch: $command->contentShowInSearch,
            contentFeaturedImage: $command->contentFeaturedImage,
            contentStatus: $command->contentStatus,
            contentCreated: $command->contentCreated,
            contentCreatedGmt: $command->contentCreatedGmt,
            contentPublished: $command->contentPublished,
            contentPublishedGmt: $command->contentPublishedGmt,
            meta: $command->meta,
            contentParent: $command->contentParent,
        );

        $this->aggregateRepository->saveAggregateRoot($content);
    }
}
