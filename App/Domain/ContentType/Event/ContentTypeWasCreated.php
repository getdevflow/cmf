<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Event;

use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class ContentTypeWasCreated extends AggregateChanged
{
    private ?ContentTypeId $contentTypeId = null;

    private ?StringLiteral $contentTypeTitle = null;

    private ?StringLiteral $contentTypeSlug = null;

    private ?StringLiteral $contentTypeDescription = null;

    public static function withData(
        ContentTypeId $contentTypeId,
        StringLiteral $contentTypeTitle,
        StringLiteral $contentTypeSlug,
        StringLiteral $contentTypeDescription
    ): ContentTypeWasCreated|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentTypeId,
            payload: [
                'content_type_id' => $contentTypeId->toNative(),
                'content_type_title' => $contentTypeTitle->toNative(),
                'content_type_slug' => $contentTypeSlug->toNative(),
                'content_type_description' => $contentTypeDescription->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'contenttype'
            ]
        );

        $event->contentTypeId = $contentTypeId;
        $event->contentTypeTitle = $contentTypeTitle;
        $event->contentTypeSlug = $contentTypeSlug;
        $event->contentTypeDescription = $contentTypeDescription;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeId(): ContentTypeId
    {
        if (is_null__($this->contentTypeId)) {
            $this->contentTypeId = ContentTypeId::fromString($this->aggregateId()->__toString());
        }

        return $this->contentTypeId;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeTitle(): StringLiteral
    {
        if (is_null__($this->contentTypeTitle)) {
            $this->contentTypeTitle = StringLiteral::fromNative($this->payload()['content_type_title']);
        }

        return $this->contentTypeTitle;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeSlug(): StringLiteral
    {
        if (is_null__($this->contentTypeSlug)) {
            $this->contentTypeSlug = StringLiteral::fromNative($this->payload()['content_type_slug']);
        }

        return $this->contentTypeSlug;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeDescription(): StringLiteral
    {
        if (is_null__($this->contentTypeDescription)) {
            $this->contentTypeDescription = StringLiteral::fromNative($this->payload()['content_type_description']);
        }

        return $this->contentTypeDescription;
    }
}
