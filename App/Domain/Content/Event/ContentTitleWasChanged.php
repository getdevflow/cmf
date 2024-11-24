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

final class ContentTitleWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?StringLiteral $contentTitle = null;

    public static function withData(
        ContentId $contentId,
        StringLiteral $contentTitle,
    ): ContentTitleWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_title' => $contentTitle->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentTitle = $contentTitle;

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
    public function contentTitle(): StringLiteral
    {
        if (is_null__($this->contentTitle)) {
            $this->contentTitle = StringLiteral::fromNative($this->payload()['content_title']);
        }

        return $this->contentTitle;
    }
}
