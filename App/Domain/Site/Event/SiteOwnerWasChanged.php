<?php

declare(strict_types=1);

namespace App\Domain\Site\Event;

use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class SiteOwnerWasChanged extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?UserId $siteOwner = null;

    public static function withData(
        SiteId $siteId,
        UserId $siteOwner,
    ): SiteOwnerWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                    'site_owner' => $siteOwner->toNative(),
                ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->siteOwner = $siteOwner;

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
    public function siteOwner(): UserId|AggregateId
    {
        if (is_null__($this->siteOwner)) {
            $this->siteOwner = UserId::fromString(userId: $this->payload()['site_owner']);
        }

        return $this->siteOwner;
    }
}
