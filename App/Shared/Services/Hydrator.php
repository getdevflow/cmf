<?php

declare(strict_types=1);

namespace App\Shared\Services;

use ReflectionClass;
use ReflectionException;

use function get_class;
use function is_object;

final class Hydrator
{
    private array $reflectionClassMap = [];

    /**
     * @param object|string $target
     * @param array $data
     * @return object
     * @throws ReflectionException
     */
    public function hydrate(object|string $target, array $data): object
    {
        $reflection = $this->getReflectionClass($target);
        $object = is_object($target) ? $target : $reflection->newInstanceWithoutConstructor();

        foreach ($data as $name => $value) {
            $property = $reflection->getProperty($name);
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }
            $property->setValue($object, $value);
        }

        return $object;
    }

    /**
     * @param object $object
     * @param array $fields
     * @return array
     * @throws ReflectionException
     */
    public function extract(object $object, array $fields): array
    {
        $reflection = $this->getReflectionClass(get_class($object));
        $result = [];

        foreach ($fields as $name) {
            $property = $reflection->getProperty($name);
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }
            $result[$property->getName()] = $property->getValue($object);
        }

        return $result;
    }

    /**
     * @param object|string $target
     * @return ReflectionClass
     * @throws ReflectionException
     */
    private function getReflectionClass(object|string $target): ReflectionClass
    {
        $className = is_object($target) ? get_class($target) : $target;
        if (!isset($this->reflectionClassMap[$className])) {
            $this->reflectionClassMap[$className] = new ReflectionClass($className);
        }
        return $this->reflectionClassMap[$className];
    }
}