<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Number\IntegerNumber;

use function Qubus\Support\Helpers\is_null__;

final class ContentShowInSearchWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?IntegerNumber $contentShowInSearch = null;

    public static function withData(
        ContentId $contentId,
        IntegerNumber $contentShowInSearch,
    ): ContentShowInSearchWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_show_in_search' => $contentShowInSearch->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentShowInSearch = $contentShowInSearch;

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

    public function contentShowInSearch(): IntegerNumber
    {
        if (is_null__($this->contentShowInSearch)) {
            $this->contentShowInSearch = IntegerNumber::fromNative($this->payload()['content_show_in_search']);
        }

        return $this->contentShowInSearch;
    }
}
