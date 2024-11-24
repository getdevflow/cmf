<?php

declare(strict_types=1);

namespace App\Domain\Site\Services;

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
use Codefy\Domain\EventSourcing\Projection;

interface SiteProjection extends Projection
{
    public function projectWhenSiteWasCreated(SiteWasCreated $event): void;

    public function projectWhenSiteNameWasChanged(SiteNameWasChanged $event): void;

    public function projectWhenSiteSlugWasChanged(SiteSlugWasChanged $event): void;

    public function projectWhenSiteDomainWasChanged(SiteDomainWasChanged $event): void;

    public function projectWhenSiteMappingWasChanged(SiteMappingWasChanged $event): void;

    public function projectWhenSitePathWasChanged(SitePathWasChanged $event): void;

    public function projectWhenSiteOwnerWasChanged(SiteOwnerWasChanged $event): void;

    public function projectWhenSiteStatusWasChanged(SiteStatusWasChanged $event): void;

    public function projectWhenSiteWasModified(SiteWasModified $event): void;

    public function projectWhenSiteWasDeleted(SiteWasDeleted $event): void;
}
