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

final class SiteMappingWasChanged extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?StringLiteral $siteMapping = null;

    public static function withData(
        SiteId $siteId,
        StringLiteral $siteMapping
    ): SiteMappingWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                'site_mapping' => $siteMapping->toNative(),
            ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->siteMapping = $siteMapping;

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
    public function siteMapping(): StringLiteral
    {
        if (is_null__($this->siteMapping)) {
            $this->siteMapping = StringLiteral::fromNative($this->payload()['site_mapping']);
        }

        return $this->siteMapping;
    }
}
