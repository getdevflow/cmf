<?php

declare(strict_types=1);

namespace App\Domain\ContentType;

use App\Domain\ContentType\Event\ContentTypeDescriptionWasChanged;
use App\Domain\ContentType\Event\ContentTypeSlugWasChanged;
use App\Domain\ContentType\Event\ContentTypeTitleWasChanged;
use App\Domain\ContentType\Event\ContentTypeWasCreated;
use App\Domain\ContentType\Event\ContentTypeWasDeleted;
use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\Domain\Aggregate\AggregateRoot;
use Codefy\Domain\Aggregate\EventSourcedAggregate;
use Exception;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class ContentType extends EventSourcedAggregate implements AggregateRoot
{
    private ?ContentTypeId $contentTypeId = null;

    private ?StringLiteral $contentTypeTitle = null;

    private ?StringLiteral $contentTypeSlug = null;

    private ?StringLiteral $contentTypeDescription = null;

    public static function createContentType(
        ContentTypeId $contentTypeId,
        StringLiteral $contentTypeTitle,
        StringLiteral $contentTypeSlug,
        StringLiteral $contentTypeDescription
    ): ContentType {
        $contentType = self::root($contentTypeId);

        $contentType->recordApplyAndPublishThat(
            ContentTypeWasCreated::withData(
                contentTypeId: $contentTypeId,
                contentTypeTitle: $contentTypeTitle,
                contentTypeSlug: $contentTypeSlug,
                contentTypeDescription: $contentTypeDescription
            )
        );

        return $contentType;
    }

    /**
     * @throws TypeException
     */
    public static function fromNative(string $contentTypeId): ContentType
    {
        return self::root(aggregateId: ContentTypeId::fromString($contentTypeId));
    }

    public function contentTypeId(): ContentTypeId
    {
        return $this->contentTypeId;
    }

    public function contentTypeTitle(): StringLiteral
    {
        return $this->contentTypeTitle;
    }

    public function contentTypeSlug(): StringLiteral
    {
        return $this->contentTypeSlug;
    }

    public function contentTypeDescription(): StringLiteral
    {
        return $this->contentTypeDescription;
    }

    /**
     * @throws Exception
     */
    public function changeTitle(StringLiteral $newTitle): void
    {
        if (is_null__($this->contentTypeTitle)) {
            throw new Exception(message: 'Content Type Title cannot be null.');
        }

        if ($this->contentTypeTitle->equals($newTitle)) {
            return;
        }

        $this->recordApplyAndPublishThat(
            event: ContentTypeTitleWasChanged::withData(
                contentTypeId: $this->contentTypeId,
                contentTypeTitle: $newTitle
            )
        );
    }

    /**
     * @throws Exception
     */
    public function changeContentTypeSlug(StringLiteral $newSlug): void
    {
        if (is_null__($this->contentTypeSlug)) {
            throw new Exception(message: 'Content Type Slug cannot be null.');
        }

        if ($this->contentTypeSlug->equals($newSlug)) {
            return;
        }

        $this->recordApplyAndPublishThat(
            event: ContentTypeSlugWasChanged::withData(contentTypeId: $this->contentTypeId, contentTypeSlug: $newSlug)
        );
    }

    /**
     * @throws Exception
     */
    public function changeContentTypeDescription(StringLiteral $newDescription): void
    {
        if (is_null__($this->contentTypeDescription)) {
            throw new Exception(message: 'Content Type Description cannot be null.');
        }
        if ($this->contentTypeDescription->equals($newDescription)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: ContentTypeDescriptionWasChanged::withData(
                contentTypeId: $this->contentTypeId,
                contentTypeDescription: $newDescription
            )
        );
    }

    /**
     * @throws TypeException
     * @throws Exception
     */
    public function changeContentTypeDeleted(ContentTypeId $contentTypeId): void
    {
        if (is_null__($this->contentTypeId)) {
            throw new Exception(message: 'Content Type ID cannot be null.');
        }
        if (!$this->contentTypeId->equals($contentTypeId)) {
            return;
        }
        $this->recordApplyAndPublishThat(event: ContentTypeWasDeleted::withData($this->contentTypeId));
    }

    /**
     * @throws TypeException
     */
    public function whenContentTypeWasCreated(ContentTypeWasCreated $event): void
    {
        $this->contentTypeId = $event->contentTypeId();
        $this->contentTypeTitle = $event->contentTypeTitle();
        $this->contentTypeSlug = $event->contentTypeSlug();
        $this->contentTypeDescription = $event->contentTypeDescription();
    }

    /**
     * @throws TypeException
     */
    public function whenContentTypeTitleWasChanged(ContentTypeTitleWasChanged $event): void
    {
        $this->contentTypeId = $event->contentTypeId();
        $this->contentTypeTitle = $event->contentTypeTitle();
    }

    /**
     * @throws TypeException
     */
    public function whenContentTypeSlugWasChanged(ContentTypeSlugWasChanged $event): void
    {
        $this->contentTypeId = $event->contentTypeId();
        $this->contentTypeSlug = $event->contentTypeSlug();
    }

    /**
     * @throws TypeException
     */
    public function whenContentTypeDescriptionWasChanged(ContentTypeDescriptionWasChanged $event): void
    {
        $this->contentTypeId = $event->contentTypeId();
        $this->contentTypeDescription = $event->contentTypeDescription();
    }

    /**
     * @throws TypeException
     */
    public function whenContentTypeWasDeleted(ContentTypeWasDeleted $event): void
    {
        $this->contentTypeId = $event->contentTypeId();
    }
}
