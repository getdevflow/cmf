<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Event\ContentAuthorWasChanged;
use App\Domain\Content\Event\ContentBodyWasChanged;
use App\Domain\Content\Event\ContentFeaturedImageWasChanged;
use App\Domain\Content\Event\ContentMetaWasChanged;
use App\Domain\Content\Event\ContentModifiedGmtWasChanged;
use App\Domain\Content\Event\ContentModifiedWasChanged;
use App\Domain\Content\Event\ContentParentWasChanged;
use App\Domain\Content\Event\ContentParentWasRemoved;
use App\Domain\Content\Event\ContentPublishedGmtWasChanged;
use App\Domain\Content\Event\ContentPublishedWasChanged;
use App\Domain\Content\Event\ContentShowInMenuWasChanged;
use App\Domain\Content\Event\ContentShowInSearchWasChanged;
use App\Domain\Content\Event\ContentSidebarWasChanged;
use App\Domain\Content\Event\ContentSlugWasChanged;
use App\Domain\Content\Event\ContentStatusWasChanged;
use App\Domain\Content\Event\ContentTitleWasChanged;
use App\Domain\Content\Event\ContentTypeWasChanged;
use App\Domain\Content\Event\ContentWasCreated;
use App\Domain\Content\Event\ContentWasDeleted;
use Codefy\Domain\EventSourcing\Projection;

interface ContentProjection extends Projection
{
    public function projectWhenContentWasCreated(ContentWasCreated $event): void;

    public function projectWhenContentTitleWasChanged(ContentTitleWasChanged $event): void;

    public function projectWhenContentSlugWasChanged(ContentSlugWasChanged $event): void;

    public function projectWhenContentBodyWasChanged(ContentBodyWasChanged $event): void;

    public function projectWhenContentAuthorWasChanged(ContentAuthorWasChanged $event): void;

    public function projectWhenContentTypeWasChanged(ContentTypeWasChanged $event): void;

    public function projectWhenContentParentWasChanged(ContentParentWasChanged $event): void;

    public function projectWhenContentParentWasRemoved(ContentParentWasRemoved $event): void;

    public function projectWhenContentSidebarWasChanged(ContentSidebarWasChanged $event): void;

    public function projectWhenContentShowInMenuWasChanged(ContentShowInMenuWasChanged $event): void;

    public function projectWhenContentShowInSearchWasChanged(ContentShowInSearchWasChanged $event): void;

    public function projectWhenContentFeaturedImageWasChanged(ContentFeaturedImageWasChanged $event): void;

    public function projectWhenContentStatusWasChanged(ContentStatusWasChanged $event): void;

    public function projectWhenContentPublishedWasChanged(ContentPublishedWasChanged $event): void;

    public function projectWhenContentPublishedGmtWasChanged(ContentPublishedGmtWasChanged $event): void;

    public function projectWhenContentModifiedWasChanged(ContentModifiedWasChanged $event): void;

    public function projectWhenContentModifiedGmtWasChanged(ContentModifiedGmtWasChanged $event): void;

    public function projectWhenContentMetaWasChanged(ContentMetaWasChanged $event): void;

    public function projectWhenContentWasDeleted(ContentWasDeleted $event): void;
}
