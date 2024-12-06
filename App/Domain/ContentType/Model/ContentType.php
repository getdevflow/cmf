<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Model;

use App\Shared\Services\Trait\HydratorAware;
use stdClass;

use function get_object_vars;

/**
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property string $description
 */
final class ContentType extends stdClass
{
    use HydratorAware;

    public function __construct(?array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Magic method for checking the existence of property.
     *
     * @param string $key Site property to check if set.
     * @return bool Whether the given property is set.
     */
    public function __isset(string $key)
    {
        if (isset($this->{$key})) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve the value of a property.
     *
     * @param string $key Property
     * @return string
     */
    public function get(string $key): string
    {
        return $this->{$key} ?? '';
    }

    /**
     * Determine whether a property is set.
     *
     * @param string $key Property
     * @return bool
     */
    public function hasProp(string $key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Return an array representation.
     *
     * @return array Array representation.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
