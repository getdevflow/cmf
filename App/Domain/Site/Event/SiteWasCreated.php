<?php

declare(strict_types=1);

namespace App\Domain\Site\Event;

use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\Services\DateTime;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class SiteWasCreated extends AggregateChanged
{
    private ?SiteId $siteId = null;

    private ?StringLiteral $siteKey = null;

    private ?StringLiteral $siteName = null;

    private ?StringLiteral $siteSlug = null;

    private ?StringLiteral $siteDomain = null;

    private ?StringLiteral $siteMapping = null;

    private ?StringLiteral $sitePath = null;

    private ?UserId $siteOwner = null;

    private ?StringLiteral $siteStatus = null;

    private ?DateTimeInterface $siteRegistered = null;

    public static function withData(
        SiteId $siteId,
        StringLiteral $siteKey,
        StringLiteral $siteName,
        StringLiteral $siteSlug,
        StringLiteral $siteDomain,
        StringLiteral $siteMapping,
        StringLiteral $sitePath,
        UserId $siteOwner,
        StringLiteral $siteStatus,
        DateTimeInterface $siteRegistered,
    ): SiteWasCreated|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $siteId,
            payload: [
                'site_id' => $siteId->toNative(),
                'site_key' => $siteKey->toNative(),
                'site_name' => $siteName->toNative(),
                'site_slug' => $siteSlug->toNative(),
                'site_domain' => $siteDomain->toNative(),
                'site_mapping' => $siteMapping->toNative(),
                'site_path' => $sitePath->toNative(),
                'site_owner' => $siteOwner->toNative(),
                'site_status' => $siteStatus->toNative(),
                'site_registered' => $siteRegistered,
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'site',
            ],
        );

        $event->siteId = $siteId;
        $event->siteKey = $siteKey;
        $event->siteName = $siteName;
        $event->siteSlug = $siteSlug;
        $event->siteDomain = $siteDomain;
        $event->siteMapping = $siteMapping;
        $event->sitePath = $sitePath;
        $event->siteOwner = $siteOwner;
        $event->siteStatus = $siteStatus;
        $event->siteRegistered = $siteRegistered;

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
    public function siteKey(): StringLiteral
    {
        if (is_null__($this->siteKey)) {
            $this->siteKey = StringLiteral::fromNative($this->payload()['site_key']);
        }

        return $this->siteKey;
    }

    /**
     * @throws TypeException
     */
    public function siteName(): StringLiteral
    {
        if (is_null__($this->siteName)) {
            $this->siteName = StringLiteral::fromNative($this->payload()['site_name']);
        }

        return $this->siteName;
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

    /**
     * @throws TypeException
     */
    public function siteOwner(): UserId
    {
        if (is_null__($this->siteOwner)) {
            $this->siteOwner = UserId::fromString($this->payload()['site_owner']);
        }

        return $this->siteOwner;
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

    public function siteRegistered(): DateTimeInterface
    {
        if (is_null__($this->siteRegistered)) {
            $this->siteRegistered = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['site_registered']))->getDateTime()
            );
        }

        return $this->siteRegistered;
    }
}
