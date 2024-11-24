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

final class ContentBodyWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?StringLiteral $contentBody = null;

    public static function withData(
        ContentId $contentId,
        StringLiteral $contentBody,
    ): ContentBodyWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_body' => $contentBody->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentBody = $contentBody;

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
    public function contentBody(): StringLiteral
    {
        if (is_null__($this->contentBody)) {
            $this->contentBody = StringLiteral::fromNative($this->payload()['content_body']);
        }

        return $this->contentBody;
    }
}
