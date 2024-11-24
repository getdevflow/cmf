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

class ContentTypeDescriptionWasChanged extends AggregateChanged
{
    private ?ContentTypeId $contentTypeId = null;

    private ?StringLiteral $contentTypeDescription = null;

    public static function withData(
        ContentTypeId $contentTypeId,
        StringLiteral $contentTypeDescription
    ): ContentTypeDescriptionWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentTypeId,
            payload: [
                'content_type_description' => $contentTypeDescription->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'contenttype',
            ],
        );

        $event->contentTypeId = $contentTypeId;
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
    public function contentTypeDescription(): StringLiteral
    {
        if (is_null__($this->contentTypeDescription)) {
            $this->contentTypeDescription = StringLiteral::fromNative($this->payload()['content_type_description']);
        }

        return $this->contentTypeDescription;
    }
}
