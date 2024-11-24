<?php

declare(strict_types=1);

namespace App\Domain\Content\Event;

use App\Domain\Content\ValueObject\ContentId;
use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class ContentAuthorWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?UserId $contentAuthor = null;

    public static function withData(
        ContentId $contentId,
        UserId $contentAuthor,
    ): ContentAuthorWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_author' => $contentAuthor->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentAuthor = $contentAuthor;

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
    public function contentAuthor(): UserId
    {
        if (is_null__($this->contentAuthor)) {
            $this->contentAuthor = UserId::fromString($this->payload()['content_author']);
        }

        return $this->contentAuthor;
    }
}
