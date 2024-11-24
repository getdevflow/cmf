<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\Services\DateTime;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

class ContentWasCreated extends AggregateChanged
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

    public static function withData(
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
        ?ArrayLiteral $meta = null,
        ContentId $contentParent = null,
    ): ContentWasCreated|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_title' => $contentTitle->toNative(),
                'content_slug' => $contentSlug->toNative(),
                'content_body' => $contentBody->toNative(),
                'content_author' => $contentAuthor->toNative(),
                'content_type' => $contentTypeSlug->toNative(),
                'content_parent' => $contentParent ? $contentParent->toNative() : null,
                'content_sidebar' => $contentSidebar->toNative(),
                'content_show_in_menu' => $contentShowInMenu->toNative(),
                'content_show_in_search' => $contentShowInSearch->toNative(),
                'content_featured_image' => $contentFeaturedImage->toNative(),
                'content_status' => $contentStatus->toNative(),
                'content_created' => $contentCreated->format('Y-m-d H:i:s'),
                'content_created_gmt' => $contentCreatedGmt->format('Y-m-d H:i:s'),
                'content_published' => $contentPublished->format('Y-m-d H:i:s'),
                'content_published_gmt' => $contentPublishedGmt->format('Y-m-d H:i:s'),
                'meta' => $meta->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ],
        );

        $event->contentId = $contentId;
        $event->contentTitle = $contentTitle;
        $event->contentSlug = $contentSlug;
        $event->contentBody = $contentBody;
        $event->contentAuthor = $contentAuthor;
        $event->contentTypeSlug = $contentTypeSlug;
        $event->contentSidebar = $contentSidebar;
        $event->contentShowInMenu = $contentShowInMenu;
        $event->contentShowInSearch = $contentShowInSearch;
        $event->contentFeaturedImage = $contentFeaturedImage;
        $event->contentStatus = $contentStatus;
        $event->contentCreated = $contentCreated;
        $event->contentCreatedGmt = $contentCreatedGmt;
        $event->contentPublished = $contentPublished;
        $event->contentPublishedGmt = $contentPublishedGmt;
        $event->contentParent = $contentParent;
        $event->meta = $meta;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function contentId(): ContentId|AggregateId
    {
        if (is_null__($this->contentId)) {
            $this->contentId = ContentId::fromString(contentId: $this->aggregateId()->__toString());
        }

        return $this->contentId;
    }

    /**
     * @throws TypeException
     */
    public function contentTitle(): StringLiteral
    {
        if (is_null__($this->contentTitle)) {
            $this->contentTitle = StringLiteral::fromNative($this->payload()['content_title']);
        }

        return $this->contentTitle;
    }

    /**
     * @throws TypeException
     */
    public function contentSlug(): StringLiteral
    {
        if (is_null__($this->contentSlug)) {
            $this->contentSlug = StringLiteral::fromNative($this->payload()['content_slug']);
        }

        return $this->contentSlug;
    }

    /**
     * @throws TypeException
     */
    public function contentBody(): StringLiteral
    {
        if (is_null__($this->contentBody)) {
            $this->contentBody = StringLiteral::fromNative($this->payload()['content_body']);
        }

        return $this->contentBody;
    }

    /**
     * @throws TypeException
     */
    public function contentAuthor(): UserId
    {
        if (is_null__($this->contentAuthor)) {
            $this->contentAuthor = UserId::fromString($this->payload()['content_author']);
        }

        return $this->contentAuthor;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeSlug(): StringLiteral
    {
        if (is_null__($this->contentTypeSlug)) {
            $this->contentTypeSlug = StringLiteral::fromNative($this->payload()['content_type']);
        }

        return $this->contentTypeSlug;
    }

    /**
     * @throws TypeException
     */
    public function contentParent(): ?ContentId
    {
        if (is_null__($this->contentParent)) {
            $this->contentParent = null;
        } else {
            $this->contentParent = ContentId::fromString($this->payload()['content_parent']);
        }

        return $this->contentParent;
    }

    public function contentSidebar(): IntegerNumber
    {
        if (is_null__($this->contentSidebar)) {
            $this->contentSidebar = IntegerNumber::fromNative($this->payload()['content_sidebar']);
        }

        return $this->contentSidebar;
    }

    public function contentShowInMenu(): IntegerNumber
    {
        if (is_null__($this->contentShowInMenu)) {
            $this->contentShowInMenu = IntegerNumber::fromNative($this->payload()['content_show_in_menu']);
        }

        return $this->contentShowInMenu;
    }

    public function contentShowInSearch(): IntegerNumber
    {
        if (is_null__($this->contentShowInSearch)) {
            $this->contentShowInSearch = IntegerNumber::fromNative($this->payload()['content_show_in_search']);
        }

        return $this->contentShowInSearch;
    }

    /**
     * @throws TypeException
     */
    public function contentFeaturedImage(): StringLiteral
    {
        if (is_null__($this->contentFeaturedImage)) {
            $this->contentFeaturedImage = StringLiteral::fromNative($this->payload()['content_featured_image']);
        }

        return $this->contentFeaturedImage;
    }

    /**
     * @throws TypeException
     */
    public function contentStatus(): StringLiteral
    {
        if (is_null__($this->contentStatus)) {
            $this->contentStatus = StringLiteral::fromNative($this->payload()['content_status']);
        }

        return $this->contentStatus;
    }

    public function contentCreated(): DateTimeInterface
    {
        if (is_null__($this->contentCreated)) {
            $this->contentCreated = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['content_created']))->getDateTime()
            );
        }

        return $this->contentCreated;
    }

    public function contentCreatedGmt(): DateTimeInterface
    {
        if (is_null__($this->contentCreatedGmt)) {
            $this->contentCreatedGmt = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['content_created_gmt']))->getDateTime()
            );
        }

        return $this->contentCreatedGmt;
    }

    public function contentPublished(): DateTimeInterface
    {
        if (is_null__($this->contentPublished)) {
            $this->contentPublished = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['content_published']))->getDateTime()
            );
        }

        return $this->contentPublished;
    }

    public function contentPublishedGmt(): DateTimeInterface
    {
        if (is_null__($this->contentPublishedGmt)) {
            $this->contentPublishedGmt = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['content_published_gmt']))->getDateTime()
            );
        }

        return $this->contentPublishedGmt;
    }

    /**
     * @throws TypeException
     */
    public function contentmeta(): ArrayLiteral
    {
        if (is_null__($this->meta)) {
            $this->meta = ArrayLiteral::fromNative($this->payload()['meta']);
        }

        return $this->meta;
    }
}
