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

final class ContentPublishedGmtWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?DateTimeInterface $contentPublishedGmt = null;

    public static function withData(
        ContentId $contentId,
        DateTimeInterface $contentPublishedGmt
    ): ContentPublishedGmtWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_published_gmt' => (string) $contentPublishedGmt
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentPublishedGmt = $contentPublishedGmt;

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

    public function contentPublishedGmt(): DateTimeInterface
    {
        if (is_null__($this->contentPublishedGmt)) {
            $this->contentPublishedGmt = QubusDateTimeImmutable::parse(
                strtotime($this->payload()['content_published_gmt']),
                'GMT'
            );
        }

        return $this->contentPublishedGmt;
    }
}
