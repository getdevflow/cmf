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

final class SiteSlugWasChanged extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?StringLiteral $siteSlug = null;

    public static function withData(
        SiteId $siteId,
        StringLiteral $siteSlug
    ): SiteSlugWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                    'site_slug' => $siteSlug->toNative(),
                ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->siteSlug = $siteSlug;

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
    public function siteSlug(): StringLiteral
    {
        if (is_null__($this->siteSlug)) {
            $this->siteSlug = StringLiteral::fromNative($this->payload()['site_slug']);
        }

        return $this->siteSlug;
    }
}
