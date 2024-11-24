<?php

declare(strict_types=1);

namespace App\Domain\Content;

use App\Domain\Content\Event\ContentAuthorWasChanged;
use App\Domain\Content\Event\ContentBodyWasChanged;
use App\Domain\Content\Event\ContentFeaturedImageWasChanged;
use App\Domain\Content\Event\ContentMetaWasChanged;
use App\Domain\Content\Event\ContentModifiedGmtWasChanged;
use App\Domain\Content\Event\ContentModifiedWasChanged;
use App\Domain\Content\Event\ContentParentWasChanged;
use App\Domain\Content\Event\ContentParentWasRemoved;
use App\Domain\Content\Event\ContentPublishedGmtWasChanged;
use App\Domain\Content\Event\ContentPublishedWasChanged;
use App\Domain\Content\Event\ContentShowInMenuWasChanged;
use App\Domain\Content\Event\ContentShowInSearchWasChanged;
use App\Domain\Content\Event\ContentSidebarWasChanged;
use App\Domain\Content\Event\ContentSlugWasChanged;
use App\Domain\Content\Event\ContentStatusWasChanged;
use App\Domain\Content\Event\ContentTitleWasChanged;
use App\Domain\Content\Event\ContentTypeWasChanged;
use App\Domain\Content\Event\ContentWasCreated;
use App\Domain\Content\Event\ContentWasDeleted;
use App\Domain\Content\ValueObject\ContentId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateRoot;
use Codefy\Domain\Aggregate\EventSourcedAggregate;
use DateTimeInterface;
use Exception;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class Content extends EventSourcedAggregate implements AggregateRoot
{
    private ?ContentId $contentId = null;

    private ?StringLiteral $contentTitle = null;

    private ?StringLiteral $contentSlug = null;

    private ?StringLiteral $contentBody = null;

    private ?UserId $contentAuthor = null;

    private ?StringLiteral $contentTypeSlug = null;

    private ?ContentId $contentParent = null;

    private ?IntegerNumber $contentSidebar = null;

    private ?IntegerNumber $contentShowInMenu = null;

    private ?IntegerNumber $contentShowInSearch = null;

    private ?StringLiteral $contentFeaturedImage = null;

    private ?ArrayLiteral $meta = null;

    private ?StringLiteral $contentStatus = null;

    private ?DateTimeInterface $contentCreated = null;

    private ?DateTimeInterface $contentCreatedGmt = null;

    private ?DateTimeInterface $contentPublished = null;

    private ?DateTimeInterface $contentPublishedGmt = null;

    private ?DateTimeInterface $contentModified = null;

    private ?DateTimeInterface $contentModifiedGmt = null;

    public static function createContent(
        ContentId $contentId,
        StringLiteral $contentTitle,
        StringLiteral $contentSlug,
        StringLiteral $contentBody,
        UserId $contentAuthor,
        StringLiteral $contentTypeSlug,
        IntegerNumber $contentSidebar,
        IntegerNumber $contentShowInMenu,
        IntegerNumber $contentShowInSearch,
        StringLiteral $contentFeaturedImage,
        StringLiteral $contentStatus,
        DateTimeInterface $contentCreated,
        DateTimeInterface $contentCreatedGmt,
        DateTimeInterface $contentPublished,
        DateTimeInterface $contentPublishedGmt,
        ArrayLiteral $meta = null,
        ?ContentId $contentParent = null,
    ): Content {
        $content = self::root(aggregateId: $contentId);

        $content->recordApplyAndPublishThat(
            ContentWasCreated::withData(
                contentId: $contentId,
                contentTitle: $contentTitle,
                contentSlug: $contentSlug,
                contentBody: $contentBody,
                contentAuthor: $contentAuthor,
                contentTypeSlug: $contentTypeSlug,
                contentSidebar: $contentSidebar,
                contentShowInMenu: $contentShowInMenu,
                contentShowInSearch: $contentShowInSearch,
                contentFeaturedImage: $contentFeaturedImage,
                contentStatus: $contentStatus,
                contentCreated: $contentCreated,
                contentCreatedGmt: $contentCreatedGmt,
                contentPublished: $contentPublished,
                contentPublishedGmt: $contentPublishedGmt,
                meta: $meta,
                contentParent: $contentParent,
            )
        );

        return $content;
    }

    /**
     * @throws TypeException
     */
    public static function fromNative(string $contentId): Content
    {
        return self::root(aggregateId: ContentId::fromString($contentId));
    }

    public function contentId(): ContentId
    {
        return $this->contentId;
    }

    public function contentTitle(): StringLiteral
    {
        return $this->contentTitle;
    }

    public function contentSlug(): StringLiteral
    {
        return $this->contentSlug;
    }

    public function contentBody(): StringLiteral
    {
        return $this->contentBody;
    }

    public function contentAuthor(): UserId
    {
        return $this->contentAuthor;
    }

    public function contentTypeSlug(): StringLiteral
    {
        return $this->contentTypeSlug;
    }

    public function contentParent(): ?ContentId
    {
        return $this->contentParent;
    }

    public function contentSidebar(): IntegerNumber
    {
        return $this->contentSidebar;
    }

    public function contentShowInMenu(): IntegerNumber
    {
        return $this->contentShowInMenu;
    }

    public function contentShowInSearch(): IntegerNumber
    {
        return $this->contentShowInSearch;
    }

    public function contentFeaturedImage(): StringLiteral
    {
        return $this->contentFeaturedImage;
    }
    public function contentStatus(): StringLiteral
    {
        return $this->contentStatus;
    }

    public function contentCreated(): DateTimeInterface
    {
        return $this->contentCreated;
    }

    public function contentCreatedGmt(): DateTimeInterface
    {
        return $this->contentCreatedGmt;
    }

    public function contentPublished(): DateTimeInterface
    {
        return $this->contentPublished;
    }

    public function contentPublishedGmt(): DateTimeInterface
    {
        return $this->contentPublishedGmt;
    }

    public function contentModified(): DateTimeInterface
    {
        return $this->contentModified;
    }

    public function contentModifiedGmt(): DateTimeInterface
    {
        return $this->contentModifiedGmt;
    }

    public function meta(): ArrayLiteral
    {
        return $this->meta;
    }

    /**
     * @throws Exception
     */
    public function changeContentTitle(StringLiteral $contentTitle): void
    {
        if ($this->contentTitle->isEmpty()) {
            throw new Exception(message: 'Content title cannot be empty.');
        }
        if ($this->contentTitle->toNative() === $contentTitle->toNative()) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: ContentTitleWasChanged::withData(contentId: $this->contentId, contentTitle: $contentTitle)
        );
    }

    /**
     * @throws Exception
     */
    public function changeContentSlug(StringLiteral $contentSlug): void
    {
        if ($contentSlug->isEmpty()) {
            throw new Exception(message: 'Content slug cannot be empty.');
        }
        if ($contentSlug->equals($this->contentSlug)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: ContentSlugWasChanged::withData(contentId: $this->contentId, contentSlug: $contentSlug)
        );
    }

    /**
     * @throws Exception
     */
    public function changeContentBody(StringLiteral $contentBody): void
    {
        if ($contentBody->isEmpty()) {
            return;
        }
        if ($contentBody->equals($this->contentBody)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentBodyWasChanged::withData($this->contentId, $contentBody));
    }

    /**
     * @throws Exception
     */
    public function changeContentAuthor(UserId $contentAuthor): void
    {
        if ($contentAuthor->isEmpty()) {
            throw new Exception(message: 'Content author cannot be empty.');
        }
        if ($contentAuthor->equals($this->contentAuthor)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentAuthorWasChanged::withData($this->contentId, $contentAuthor));
    }

    /**
     * @throws Exception
     */
    public function changeContentType(StringLiteral $contentTypeSlug): void
    {
        if ($contentTypeSlug->isEmpty()) {
            throw new Exception(message: 'Content type cannot be empty.');
        }
        if ($contentTypeSlug->equals($this->contentTypeSlug)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentTypeWasChanged::withData($this->contentId, $contentTypeSlug));
    }

    /**
     * @throws Exception
     */
    public function changeContentParent(ContentId $contentParent): void
    {
        if ($contentParent->isEmpty()) {
            return;
        }

        if ($contentParent->equals($this->contentParent)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentParentWasChanged::withData($this->contentId, $contentParent));
    }

    /**
     * @throws Exception
     */
    public function changeContentParentWasRemoved(?ContentId $contentParent = null): void
    {
        if (is_null__($this->contentParent) && is_null__($contentParent)) {
            return;
        }

        if ($this->contentParent->equals($contentParent ?? new ContentId())) {
            return;
        }

        $this->recordApplyAndPublishThat(ContentParentWasRemoved::withData($this->contentId, $contentParent));
    }

    /**
     * @throws Exception
     */
    public function changeContentSidebar(IntegerNumber $contentSidebar): void
    {
        if ($contentSidebar->toInteger()->toNative() < 0) {
            throw new Exception(message: 'Content sidebar must be an absolute integer.');
        }
        if ($contentSidebar->equals($this->contentSidebar)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentSidebarWasChanged::withData($this->contentId, $contentSidebar));
    }

    /**
     * @throws Exception
     */
    public function changeContentShowInMenu(IntegerNumber $showInMenu): void
    {
        if ($showInMenu->toInteger()->toNative() < 0) {
            throw new Exception(message: 'Show in menu must be an absolute integer.');
        }
        if ($showInMenu->equals($this->contentShowInMenu)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentShowInMenuWasChanged::withData($this->contentId, $showInMenu));
    }

    /**
     * @throws Exception
     */
    public function changeContentShowInSearch(IntegerNumber $showInSearch): void
    {
        if ($showInSearch->toInteger()->toNative() < 0) {
            throw new Exception(message: 'Show in search must be an absolute integer.');
        }
        if ($showInSearch->equals($this->contentShowInSearch)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentShowInSearchWasChanged::withData($this->contentId, $showInSearch));
    }

    /**
     * @throws Exception
     */
    public function changeContentFeaturedImage(StringLiteral $contentFeaturedImage): void
    {
        if ($contentFeaturedImage->equals($this->contentFeaturedImage)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            ContentFeaturedImageWasChanged::withData($this->contentId, $contentFeaturedImage)
        );
    }

    /**
     * @throws Exception
     */
    public function changeContentStatus(StringLiteral $contentStatus): void
    {
        if ($contentStatus->isEmpty()) {
            throw new Exception(message: 'Content status cannot be empty.');
        }
        if ($contentStatus->equals($this->contentStatus)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentStatusWasChanged::withData($this->contentId, $contentStatus));
    }

    /**
     * @throws Exception
     */
    public function changeContentPublished(DateTimeInterface $contentPublished): void
    {
        if (empty($this->contentPublished)) {
            throw new Exception(message: 'Content published cannot be empty.');
        }
        if ($this->contentPublished->getTimestamp() === $contentPublished->getTimestamp()) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentPublishedWasChanged::withData($this->contentId, $contentPublished));
    }

    /**
     * @throws Exception
     */
    public function changeContentPublishedGmt(DateTimeInterface $contentPublishedGmt): void
    {
        if (empty($this->contentPublishedGmt)) {
            throw new Exception(message: 'Content published gmt cannot be empty.');
        }
        if ($this->contentPublishedGmt->getTimestamp() === $contentPublishedGmt->getTimestamp()) {
            return;
        }
        $this->recordApplyAndPublishThat(
            ContentPublishedGmtWasChanged::withData($this->contentId, $contentPublishedGmt)
        );
    }

    /**
     * @throws Exception
     */
    public function changeContentModified(DateTimeInterface $contentModified): void
    {
        if (
                !is_null__($this->contentModified) &&
                ($this->contentModified->getTimestamp() === $contentModified->getTimestamp())
        ) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentModifiedWasChanged::withData($this->contentId, $contentModified));
    }

    /**
     * @throws Exception
     */
    public function changeContentModifiedGmt(DateTimeInterface $contentModifiedGmt): void
    {
        if (
                !is_null__($this->contentModifiedGmt) &&
                ($this->contentModifiedGmt->getTimestamp() === $contentModifiedGmt->getTimestamp())
        ) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentModifiedGmtWasChanged::withData($this->contentId, $contentModifiedGmt));
    }

    /**
     * @param ContentId $contentId
     * @return void
     * @throws Exception
     */
    public function changeContentDeleted(ContentId $contentId): void
    {
        if ($contentId->isEmpty()) {
            throw new Exception(message: 'Content id cannot be null.');
        }
        if (!$contentId->equals($this->contentId)) {
            return;
        }
        $this->recordApplyAndPublishThat(ContentWasDeleted::withData($this->contentId));
    }

    public function changeContentMeta(ArrayLiteral $meta): void
    {
        if ($meta->isEmpty()) {
            return;
        }

        if ($meta->equals($this->meta)) {
            return;
        }

        $this->recordApplyAndPublishThat(ContentMetaWasChanged::withData($this->contentId, $meta));
    }

    /**
     * @throws TypeException
     */
    public function whenContentWasCreated(ContentWasCreated $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentTitle = $event->contentTitle();
        $this->contentSlug = $event->contentSlug();
        $this->contentBody = $event->contentBody();
        $this->contentAuthor = $event->contentAuthor();
        $this->contentTypeSlug = $event->contentTypeSlug();
        $this->contentSidebar = $event->contentSidebar();
        $this->contentShowInMenu = $event->contentShowInMenu();
        $this->contentShowInSearch = $event->contentShowInSearch();
        $this->contentFeaturedImage = $event->contentFeaturedImage();
        $this->contentStatus = $event->contentStatus();
        $this->contentCreated = $event->contentCreated();
        $this->contentCreatedGmt = $event->contentCreatedGmt();
        $this->contentPublished = $event->contentPublished();
        $this->contentPublishedGmt = $event->contentPublishedGmt();
        $this->meta = $event->contentmeta();
        $this->contentParent = $event->contentParent();
    }

    /**
     * @throws TypeException
     */
    public function whenContentTitleWasChanged(ContentTitleWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentTitle = $event->contentTitle();
    }

    /**
     * @throws TypeException
     */
    public function whenContentSlugWasChanged(ContentSlugWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentSlug = $event->contentSlug();
    }

    /**
     * @throws TypeException
     */
    public function whenContentBodyWasChanged(ContentBodyWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentBody = $event->contentBody();
    }

    /**
     * @throws TypeException
     */
    public function whenContentAuthorWasChanged(ContentAuthorWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentAuthor = $event->contentAuthor();
    }

    /**
     * @throws TypeException
     */
    public function whenContentTypeWasChanged(ContentTypeWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentTypeSlug = $event->contentTypeSlug();
    }

    /**
     * @throws TypeException
     */
    public function whenContentParentWasChanged(ContentParentWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentParent = $event->contentParent();
    }

    /**
     * @throws TypeException
     */
    public function whenContentSidebarWasChanged(ContentSidebarWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentSidebar = $event->contentSidebar();
    }

    /**
     * @throws TypeException
     */
    public function whenContentShowInMenuWasChanged(ContentShowInMenuWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentShowInMenu = $event->contentShowInMenu();
    }

    /**
     * @throws TypeException
     */
    public function whenContentShowInSearchWasChanged(ContentShowInSearchWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentShowInSearch = $event->contentShowInSearch();
    }

    /**
     * @throws TypeException
     */
    public function whenContentFeaturedImageWasChanged(ContentFeaturedImageWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentFeaturedImage = $event->contentFeaturedImage();
    }

    /**
     * @throws TypeException
     */
    public function whenContentAttributesWasChanged(ContentMetaWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->meta = $event->contentmeta();
    }

    /**
     * @throws TypeException
     */
    public function whenContentStatusWasChanged(ContentStatusWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentStatus = $event->contentStatus();
    }

    /**
     * @throws TypeException
     */
    public function whenContentPublishedWasChanged(ContentPublishedWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentPublished = $event->contentPublished();
    }

    /**
     * @throws TypeException
     */
    public function whenContentPublishedGmtWasChanged(ContentPublishedGmtWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentPublishedGmt = $event->contentPublishedGmt();
    }

    /**
     * @throws TypeException
     */
    public function whenContentModifiedWasChanged(ContentModifiedWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentModified = $event->contentModified();
    }

    /**
     * @throws TypeException
     */
    public function whenContentModifiedGmtWasChanged(ContentModifiedGmtWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentModifiedGmt = $event->contentModifiedGmt();
    }

    /**
     * @throws TypeException
     */
    public function whenContentParentWasRemoved(ContentParentWasRemoved $event): void
    {
        $this->contentId = $event->contentId();
        $this->contentParent = $event->contentParent();
    }

    /**
     * @throws TypeException
     */
    public function whenContentMetaWasChanged(ContentMetaWasChanged $event): void
    {
        $this->contentId = $event->contentId();
        $this->meta = $event->contentMeta();
    }

    /**
     * @throws TypeException
     */
    public function whenContentWasDeleted(ContentWasDeleted $event): void
    {
        $this->contentId = $event->contentId();
    }
}
