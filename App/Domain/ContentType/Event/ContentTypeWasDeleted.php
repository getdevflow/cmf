<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Event;

use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class ContentTypeWasDeleted extends AggregateChanged
{
    private ?ContentTypeId $contentTypeId = null;

    public static function withData(
        ContentTypeId $contentTypeId,
    ): ContentTypeWasDeleted|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentTypeId,
            payload: [],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'contenttype',
            ],
        );

        $event->contentTypeId = $contentTypeId;

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
}
