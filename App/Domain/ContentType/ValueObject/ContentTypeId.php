<?php

declare(strict_types=1);

namespace App\Domain\ContentType\ValueObject;

use App\Domain\ContentType\ContentType;
use Codefy\Domain\Aggregate\AggregateId;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Ulid;

class ContentTypeId extends Ulid implements AggregateId
{
    public function aggregateClassName(): string
    {
        return ContentType::className();
    }

    /**
     * @throws TypeException
     */
    public static function fromString(string $contentTypeId): ContentTypeId
    {
        return new self(value: $contentTypeId);
    }
}
