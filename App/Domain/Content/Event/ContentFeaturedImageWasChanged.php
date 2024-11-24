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

final class ContentFeaturedImageWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?StringLiteral $contentFeaturedImage = null;

    public static function withData(
        ContentId $contentId,
        StringLiteral $contentFeaturedImage,
    ): ContentFeaturedImageWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_featured_image' => $contentFeaturedImage->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentFeaturedImage = $contentFeaturedImage;

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
    public function contentFeaturedImage(): StringLiteral
    {
        if (is_null__($this->contentFeaturedImage)) {
            $this->contentFeaturedImage = StringLiteral::fromNative($this->payload()['content_featured_image']);
        }

        return $this->contentFeaturedImage;
    }
}
