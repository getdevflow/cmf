<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class ContentWasDeleted extends AggregateChanged
{
    private ?ContentId $contentId = null;

    public static function withData(
        ContentId $contentId,
    ): ContentWasDeleted|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function contentId(): ContentId
    {
        if (is_null__($this->contentId)) {
            $this->contentId = ContentId::fromString($this->aggregateId()->__toString());
        }

        return $this->contentId;
    }
}
