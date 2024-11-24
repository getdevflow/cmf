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

final class ContentShowInMenuWasChanged extends AggregateChanged
{
    private ?ContentId $contentId = null;

    private ?IntegerNumber $contentShowInMenu = null;

    public static function withData(
        ContentId $contentId,
        IntegerNumber $contentShowInMenu,
    ): ContentShowInMenuWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $contentId,
            payload: [
                'content_show_in_menu' => $contentShowInMenu->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'content',
            ]
        );

        $event->contentId = $contentId;
        $event->contentShowInMenu = $contentShowInMenu;

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

    public function contentShowInMenu(): IntegerNumber
    {
        if (is_null__($this->contentShowInMenu)) {
            $this->contentShowInMenu = IntegerNumber::fromNative($this->payload()['content_show_in_menu']);
        }

        return $this->contentShowInMenu;
    }
}
