<?php

declare(strict_types=1);

namespace App\Domain\Content\ValueObject;

use App\Domain\Content\Content;
use Codefy\Domain\Aggregate\AggregateId;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Ulid;

class ContentId extends Ulid implements AggregateId
{
    public function aggregateClassName(): string
    {
        return Content::className();
    }

    /**
     * @throws TypeException
     */
    public static function fromString(?string $contentId = null): ContentId
    {
        return new self(value: $contentId);
    }
}
