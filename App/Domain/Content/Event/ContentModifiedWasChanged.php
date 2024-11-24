<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;

use function Qubus\Support\Helpers\is_null__;
use function strtotime;

final class ContentModifiedWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?DateTimeInterface $contentModified = null;

    public static function withData(
        ContentId $contentId,
        DateTimeInterface $contentModified,
    ): ContentModifiedWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_modified' => $contentModified,
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentModified = $contentModified;

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

    public function contentModified(): DateTimeInterface
    {
        if (is_null__($this->contentModified)) {
            $this->contentModified = QubusDateTimeImmutable::parse(
                strtotime($this->payload()['content_modified'])
            );
        }

        return $this->contentModified;
    }
}
