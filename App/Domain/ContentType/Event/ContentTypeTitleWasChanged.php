<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Event;

use App\Domain\ContentType\ValueObject\ContentTypeId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

class ContentTypeTitleWasChanged extends AggregateChanged
{
    private ?ContentTypeId $contentTypeId = null;

    private ?StringLiteral $contentTypeTitle = null;

    public static function withData(
        ContentTypeId $contentTypeId,
        StringLiteral $contentTypeTitle
    ): ContentTypeTitleWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentTypeId,
            payload: [
                'content_type_title' => $contentTypeTitle->toNative(),
            ],
            metadata:[
                Metadata::AGGREGATE_TYPE => 'contenttype',
            ],
        );

        $event->contentTypeId = $contentTypeId;
        $event->contentTypeTitle = $contentTypeTitle;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeId(): ContentTypeId
    {
        if (is_null__($this->contentTypeId)) {
            $this->contentTypeId = ContentTypeId::fromString($this->aggregateId()->__toString());
        }

        return $this->contentTypeId;
    }

    /**
     * @throws TypeException
     */
    public function contentTypeTitle(): StringLiteral
    {
        if (is_null__($this->contentTypeTitle)) {
            $this->contentTypeTitle = StringLiteral::fromNative($this->payload()['content_type_title']);
        }

        return $this->contentTypeTitle;
    }
}
