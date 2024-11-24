<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use JsonException;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Util;
use Qubus\ValueObjects\ValueObject;

use function func_get_arg;
use function is_array;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class ArrayLiteral implements ValueObject
{
    private array $value = [];

    /**
     * @throws TypeException
     */
    public function __construct(array $data = [])
    {
        if (false === is_array($data)) {
            throw new TypeException(
                sprintf(
                    'Argument "%s" is invalid. Must enter an array.',
                    $data
                )
            );
        }
        $this->value = $data;
    }

    /**
     * Returns an array object.
     *
     * @throws TypeException
     */
    public static function fromNative(): self
    {
        $meta = func_get_arg(0);
        return new self($meta);
    }

    /**
     * Returns the value of the array.
     *
     * @return array
     */
    public function toNative(): array
    {
        return $this->value;
    }

    /**
     * Tells whether two arrays are equal by comparing their values.
     *
     * @param ArrayLiteral|ValueObject $object
     * @return bool
     */
    public function equals(ArrayLiteral|ValueObject $object): bool
    {
        if (false === Util::classEquals($this, $object)) {
            return false;
        }

        return $this->toNative() === $object->toNative();
    }

    /**
     * Tells whether the array is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     * Returns the array value itself.
     *
     * @throws JsonException
     */
    public function __toString()
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR);
    }
}
