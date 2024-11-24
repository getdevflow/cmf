<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class ContentParentWasRemoved extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?ContentId $contentParent = null;

    public static function withData(
        ContentId $contentId,
        ?ContentId $contentParent = null,
    ): ContentParentWasRemoved|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_parent' => is_null__($contentParent) ? null : $contentParent->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentParent = $contentParent;

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

    /**
     * @throws TypeException
     */
    public function contentParent(): ?ContentId
    {
        if (is_null__($this->contentParent)) {
            $this->contentParent = ContentId::fromString($this->payload()['content_parent']);
        }

        return $this->contentParent;
    }
}
