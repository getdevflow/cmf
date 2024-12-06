<?php

declare(strict_types=1);

namespace App\Shared\Services\Trait;

use App\Shared\Services\Hydrator;
use ReflectionException;

trait HydratorAware
{
    /**
     * @throws ReflectionException
     */
    public static function hydrate(array $data): object
    {
        return (new Hydrator())->hydrate(target: self::class, data: $data);
    }

    /**
     * @throws ReflectionException
     */
    public static function extract(array $data): array
    {
        return (new Hydrator())->extract(object: (object) self::class, fields: $data);
    }
}
