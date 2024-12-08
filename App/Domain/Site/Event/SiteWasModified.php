<?php

declare(strict_types=1);

namespace App\Domain\Site\Event;

use App\Domain\Site\ValueObject\SiteId;
use App\Shared\Services\DateTime;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;

use function Qubus\Support\Helpers\is_null__;

final class SiteWasModified extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?DateTimeInterface $siteModified = null;

    public static function withData(
        SiteId $siteId,
        DateTimeInterface $siteModified,
    ): SiteWasModified|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                    'site_modified' => (string) $siteModified,
                ],
            metadata: [
                    Metadata::AGGREGATE_TYPE => 'site'
                ],
        );

        $event->siteId = $siteId;
        $event->siteModified = $siteModified;

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

    public function siteModified(): DateTimeInterface
    {
        if (is_null__($this->siteModified)) {
            $this->siteModified = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['site_modified']))->getDateTime()
            );
        }

        return $this->siteModified;
    }
}
