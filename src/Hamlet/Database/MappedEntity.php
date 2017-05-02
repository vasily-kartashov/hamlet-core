<?php

namespace Hamlet\Database;

use Exception;
use ReflectionClass;

abstract class MappedEntity
{

    /**
     * @param array $data
     * @param bool $ignoreUnmapped
     * @return static
     * @throws Exception
     */
    public static function from(array $data, bool $ignoreUnmapped = false)
    {
        $type = new ReflectionClass(static::class);
        $object = new static();
        foreach ($data as $name => $value) {
            if ($ignoreUnmapped && !$type->hasProperty($name)) {
                continue;
            }
            $property = $type->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
        foreach ($type->getProperties() as $property) {
            if (!array_key_exists($property->getName(), $data)) {
                throw new Exception('Property ' . $type->getName() . '::' . $property->getName()
                    . ' not set in ' . json_encode($data));
            }
        }
        return $object;
    }
}
