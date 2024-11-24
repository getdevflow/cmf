<?php

declare(strict_types=1);

namespace App\Domain\Site\Model;

final class Site
{
    public ?string $id = null;

    public ?string $key = null;

    public ?string $name = null;

    public ?string $slug = null;

    public ?string $domain = null;

    public ?string $mapping = null;

    public ?string $path = null;

    public ?string $owner = null;

    public ?string $status = null;

    public ?string $registered = null;

    public ?string $modified = null;

    public function __construct(array $data = [])
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
