<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Services;

use App\Domain\ContentType\Event\ContentTypeDescriptionWasChanged;
use App\Domain\ContentType\Event\ContentTypeSlugWasChanged;
use App\Domain\ContentType\Event\ContentTypeTitleWasChanged;
use App\Domain\ContentType\Event\ContentTypeWasCreated;
use App\Domain\ContentType\Event\ContentTypeWasDeleted;
use Codefy\Domain\EventSourcing\Projection;

interface ContentTypeProjection extends Projection
{
    public function projectWhenContentTypeWasCreated(ContentTypeWasCreated $event): void;

    public function projectWhenContentTypeTitleWasChanged(ContentTypeTitleWasChanged $event): void;

    public function projectWhenContentTypeSlugWasChanged(ContentTypeSlugWasChanged $event): void;

    public function projectWhenContentTypeDescriptionWasChanged(ContentTypeDescriptionWasChanged $event): void;

    public function projectWhenContentTypeWasDeleted(ContentTypeWasDeleted $event): void;
}
