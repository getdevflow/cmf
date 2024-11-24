<?php

declare(strict_types=1);

namespace App\Domain\Site\Event;

use App\Domain\Site\ValueObject\SiteId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class SiteStatusWasChanged extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?StringLiteral $siteStatus = null;

    public static function withData(
        SiteId $siteId,
        StringLiteral $siteStatus
    ): SiteStatusWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                    'site_status' => $siteStatus->toNative(),
                ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->siteStatus = $siteStatus;

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

    /**
     * @throws TypeException
     */
    public function siteStatus(): StringLiteral
    {
        if (is_null__($this->siteStatus)) {
            $this->siteStatus = StringLiteral::fromNative($this->payload()['site_status']);
        }

        return $this->siteStatus;
    }
}
