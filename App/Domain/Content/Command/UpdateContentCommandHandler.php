<?php

declare(strict_types=1);

namespace App\Domain\Content\Command;

use App\Domain\Content\Content;
use App\Domain\Content\Repository\ContentRepository;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;

use function Qubus\Support\Helpers\is_null__;

class UpdateContentCommandHandler implements CommandHandler
{
    public function __construct(public ContentRepository $aggregateRepository)
    {
    }

    /**
     * @inheritDoc
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(UpdateContentCommand|Command $command): void
    {
        /** @var Content $content */
        $content = $this->aggregateRepository->loadAggregateRoot($command->contentId);

        $content->changeContentTitle($command->contentTitle);
        $content->changeContentSlug($command->contentSlug);
        $content->changeContentBody($command->contentBody);
        $content->changeContentAuthor($command->contentAuthor);
        $content->changeContentType($command->contentTypeSlug);
        if (!is_null__($command->contentParent)) {
            $content->changeContentParent($command->contentParent);
        } else {
            $content->changeContentParentWasRemoved($command->contentParent);
        }
        $content->changeContentSidebar($command->contentSidebar);
        $content->changeContentShowInMenu($command->contentShowInMenu);
        $content->changeContentShowInSearch($command->contentShowInSearch);
        $content->changeContentFeaturedImage($command->contentFeaturedImage);
        $content->changeContentStatus($command->contentStatus);
        $content->changeContentMeta($command->meta);
        $content->changeContentPublished($command->contentPublished);
        $content->changeContentPublishedGmt($command->contentPublishedGmt);
        if ($content->hasRecordedEvents()) {
            $content->changeContentModified($command->contentModified);
            $content->changeContentModifiedGmt($command->contentModifiedGmt);
        }

        $this->aggregateRepository->saveAggregateRoot($content);
    }
}
