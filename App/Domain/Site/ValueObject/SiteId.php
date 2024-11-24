<?php

declare(strict_types=1);

namespace App\Domain\Site\ValueObject;

use App\Domain\Site\Site;
use Codefy\Domain\Aggregate\AggregateId;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Ulid;

class SiteId extends Ulid implements AggregateId
{
    public function aggregateClassName(): string
    {
        return Site::className();
    }

    /**
     * @throws TypeException
     */
    public static function fromString(string $siteId): SiteId
    {
        return new self(value: $siteId);
    }
}
