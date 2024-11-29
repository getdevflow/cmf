<?php

declare(strict_types=1);

namespace App\Domain\Site;

use App\Domain\Site\Event\SiteDomainWasChanged;
use App\Domain\Site\Event\SiteMappingWasChanged;
use App\Domain\Site\Event\SiteNameWasChanged;
use App\Domain\Site\Event\SiteOwnerWasChanged;
use App\Domain\Site\Event\SitePathWasChanged;
use App\Domain\Site\Event\SiteSlugWasChanged;
use App\Domain\Site\Event\SiteStatusWasChanged;
use App\Domain\Site\Event\SiteWasCreated;
use App\Domain\Site\Event\SiteWasDeleted;
use App\Domain\Site\Event\SiteWasModified;
use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\Aggregate\AggregateRoot;
use Codefy\Domain\Aggregate\EventSourcedAggregate;
use DateTimeInterface;
use Exception;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class Site extends EventSourcedAggregate implements AggregateRoot
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

    private ?DateTimeInterface $siteModified = null;

    public static function createSite(
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
    ): Site {
        $site = self::root($siteId);

        $site->recordApplyAndPublishThat(
            SiteWasCreated::withData(
                $siteId,
                $siteKey,
                $siteName,
                $siteSlug,
                $siteDomain,
                $siteMapping,
                $sitePath,
                $siteOwner,
                $siteStatus,
                $siteRegistered,
            )
        );

        return $site;
    }

    public static function fromNative(SiteId $siteId): Site
    {
        return self::root($siteId);
    }

    public function siteId(): SiteId|AggregateId
    {
        return $this->siteId;
    }

    public function siteKey(): StringLiteral
    {
        return $this->siteKey;
    }

    public function siteName(): StringLiteral
    {
        return $this->siteName;
    }

    public function siteSlug(): StringLiteral
    {
        return $this->siteSlug;
    }

    public function siteDomain(): StringLiteral
    {
        return $this->siteDomain;
    }

    public function siteMapping(): StringLiteral
    {
        return $this->siteMapping;
    }

    public function sitePath(): StringLiteral
    {
        return $this->sitePath;
    }

    public function siteOwner(): UserId
    {
        return $this->siteOwner;
    }

    public function siteStatus(): StringLiteral
    {
        return $this->siteStatus;
    }

    public function siteRegistered(): DateTimeInterface
    {
        return $this->siteRegistered;
    }

    public function siteModified(): DateTimeInterface
    {
        return $this->siteModified;
    }

    /**
     * @throws Exception
     */
    public function changeSiteName(StringLiteral $siteName): void
    {
        if ($siteName->isEmpty()) {
            throw new Exception(message: 'Site name cannot be empty.');
        }

        if ($siteName->equals($this->siteName)) {
            return;
        }

        $this->recordApplyAndPublishThat(
            SiteNameWasChanged::withData($this->siteId, $siteName)
        );
    }

    /**
     * @throws Exception
     */
    public function changeSiteSlug(StringLiteral $siteSlug): void
    {
        if ($siteSlug->isEmpty()) {
            throw new Exception(message: 'Site slug cannot be empty.');
        }

        if ($siteSlug->equals($this->siteSlug)) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteSlugWasChanged::withData($this->siteId, $siteSlug));
    }

    /**
     * @throws Exception
     */
    public function changeSiteDomain(StringLiteral $siteDomain): void
    {
        if ($siteDomain->isEmpty()) {
            throw new Exception(message: 'Site domain cannot be empty.');
        }

        if ($siteDomain->equals($this->siteDomain)) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteDomainWasChanged::withData($this->siteId, $siteDomain));
    }

    /**
     * @throws Exception
     */
    public function changeSiteMapping(StringLiteral $siteMapping): void
    {
        if ($siteMapping->isEmpty()) {
            return;
        }

        if ($siteMapping->equals($this->siteMapping)) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteMappingWasChanged::withData($this->siteId, $siteMapping));
    }

    /**
     * @throws Exception
     */
    public function changeSitePath(StringLiteral $sitePath): void
    {
        if ($sitePath->isEmpty()) {
            throw new Exception(message: 'Site path cannot be empty.');
        }

        if ($sitePath->equals($this->sitePath)) {
            return;
        }

        $this->recordApplyAndPublishThat(SitePathWasChanged::withData($this->siteId, $sitePath));
    }

    /**
     * @throws Exception
     */
    public function changeSiteOwner(UserId $siteOwner): void
    {
        if ($siteOwner->isEmpty()) {
            throw new Exception(message: 'Owner cannot be empty.');
        }

        if ($siteOwner->equals($this->siteOwner)) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteOwnerWasChanged::withData($this->siteId, $siteOwner));
    }

    /**
     * @throws Exception
     */
    public function changeSiteStatus(StringLiteral $siteStatus): void
    {
        if ($siteStatus->isEmpty()) {
            throw new Exception(message: 'Site status cannot be empty.');
        }

        if ($siteStatus->equals($this->siteStatus)) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteStatusWasChanged::withData($this->siteId, $siteStatus));
    }

    /**
     * @throws Exception
     */
    public function changeSiteModified(DateTimeInterface $siteModified): void
    {
        if (empty($this->siteModified)) {
            return;
        }

        if ($this->siteModified->getTimestamp() === $siteModified->getTimestamp()) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteWasModified::withData($this->siteId, $siteModified));
    }

    /**
     * @throws Exception
     */
    public function changeSiteDeleted(SiteId $siteId): void
    {
        if (is_null__($siteId)) {
            throw new Exception(message: 'Site id cannot be null.');
        }

        if (!$siteId->equals($this->siteId)) {
            return;
        }

        $this->recordApplyAndPublishThat(SiteWasDeleted::withData($this->siteId));
    }

    /**
     * @throws TypeException
     */
    public function whenSiteWasCreated(SiteWasCreated $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteKey = $event->siteKey();
        $this->siteName = $event->siteName();
        $this->siteSlug = $event->siteSlug();
        $this->siteDomain = $event->siteDomain();
        $this->siteMapping = $event->siteMapping();
        $this->sitePath = $event->sitePath();
        $this->siteOwner = $event->siteOwner();
        $this->siteStatus = $event->siteStatus();
        $this->siteRegistered = $event->siteRegistered();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteNameWasChanged(SiteNameWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteName = $event->siteName();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteSlugWasChanged(SiteSlugWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteSlug = $event->siteSlug();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteDomainWasChanged(SiteDomainWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteDomain = $event->siteDomain();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteMappingWasChanged(SiteMappingWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteMapping = $event->siteMapping();
    }

    /**
     * @throws TypeException
     */
    public function whenSitePathWasChanged(SitePathWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->sitePath = $event->sitePath();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteOwnerWasChanged(SiteOwnerWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteOwner = $event->siteOwner();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteStatusWasChanged(SiteStatusWasChanged $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteStatus = $event->siteStatus();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteWasModified(SiteWasModified $event): void
    {
        $this->siteId = $event->siteId();
        $this->siteModified = $event->siteModified();
    }

    /**
     * @throws TypeException
     */
    public function whenSiteWasDeleted(SiteWasDeleted $event): void
    {
        $this->siteId = $event->siteId();
    }
}
