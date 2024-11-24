<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Uuid;

class UserToken extends Uuid
{
    /**
     * @throws TypeException
     */
    public static function fromString(string $userToken): UserToken
    {
        return new self(value: $userToken);
    }

    public function token(): string
    {
        return $this->toNative();
    }
}
