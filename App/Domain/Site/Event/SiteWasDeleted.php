<?php

declare(strict_types=1);

namespace App\Domain\Site\Event;

use App\Domain\Site\ValueObject\SiteId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class SiteWasDeleted extends AggregateChanged
{
    private ?SiteId $siteId = null;

    public static function withData(
        SiteId $siteId,
    ): SiteWasDeleted|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function siteId(): SiteId|AggregateId
    {
        if (is_null__($this->siteId)) {
            $this->siteId = SiteId::fromString(siteId: $this->aggregateId()->__toString());
        }

        return $this->siteId;
    }
}
