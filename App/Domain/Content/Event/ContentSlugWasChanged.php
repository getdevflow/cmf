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

final class ContentSlugWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?StringLiteral $contentSlug = null;

    public static function withData(
        ContentId $contentId,
        StringLiteral $contentSlug,
    ): ContentSlugWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_slug' => $contentSlug,
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentSlug = $contentSlug;

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
    public function contentSlug(): StringLiteral
    {
        if (is_null__($this->contentSlug)) {
            $this->contentSlug = StringLiteral::fromNative($this->payload()['content_slug']);
        }

        return $this->contentSlug;
    }
}
