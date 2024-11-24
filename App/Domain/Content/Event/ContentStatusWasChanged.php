<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class ContentStatusWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?StringLiteral $contentStatus = null;

    public static function withData(
        ContentId $contentId,
        StringLiteral $contentStatus,
    ): ContentStatusWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_status' => $contentStatus->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentStatus = $contentStatus;

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
    public function contentStatus(): StringLiteral
    {
        if (is_null__($this->contentStatus)) {
            $this->contentStatus = StringLiteral::fromNative($this->payload()['content_status']);
        }

        return $this->contentStatus;
    }
}
