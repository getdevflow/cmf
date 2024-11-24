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

final class SiteDomainWasChanged extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?StringLiteral $siteDomain = null;

    public static function withData(
        SiteId $siteId,
        StringLiteral $siteDomain
    ): SiteDomainWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                    'site_domain' => $siteDomain->toNative(),
                ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->siteDomain = $siteDomain;

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
    public function siteDomain(): StringLiteral
    {
        if (is_null__($this->siteDomain)) {
            $this->siteDomain = StringLiteral::fromNative($this->payload()['site_domain']);
        }

        return $this->siteDomain;
    }
}
