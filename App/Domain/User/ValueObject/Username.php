<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use Qubus\Exception\Data\TypeException;
use Qubus\Support\Assertion;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Security\Helpers\trim__;
use function Qubus\Support\Helpers\remove_accents;

class Username extends StringLiteral
{
    public function __construct(string $value)
    {
        $username = trim__($value);
        $username = remove_accents($username);

        Assertion::regex(
            value: $username,
            pattern: '/^\w{3,60}$/',
            message: 'Username must have a length between 3 to 60 characters.'
        );

        $this->value = $username;
    }

    /**
     * @throws TypeException
     */
    public static function fromString(string $userToken): Username
    {
        return new self(value: $userToken);
    }
}
