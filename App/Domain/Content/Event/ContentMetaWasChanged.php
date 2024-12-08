<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

class ContentMetaWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?ArrayLiteral $meta = null;

    public static function withData(
        ContentId $contentId,
        ArrayLiteral $meta
    ): ContentMetaWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'meta' => $meta->toNative()
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->meta -= $meta;

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
    public function contentmeta(): ArrayLiteral
    {
        if (is_null__($this->meta)) {
            $this->meta = ArrayLiteral::fromNative($this->payload()['meta']);
        }

        return $this->meta;
    }
}
