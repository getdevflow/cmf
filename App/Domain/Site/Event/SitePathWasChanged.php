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

final class SitePathWasChanged extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?StringLiteral $sitePath = null;

    public static function withData(
        SiteId $siteId,
        StringLiteral $sitePath
    ): SitePathWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                    'site_path' => $sitePath->toNative(),
                ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->sitePath = $sitePath;

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
    public function sitePath(): StringLiteral
    {
        if (is_null__($this->sitePath)) {
            $this->sitePath = StringLiteral::fromNative($this->payload()['site_path']);
        }

        return $this->sitePath;
    }
}
