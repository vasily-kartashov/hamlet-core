<?php

namespace Hamlet\Database {

    use ReflectionClass;

    abstract class MappedEntity {

        /**
         * @param array $properties
         * @param bool $ignoreUnmapped
         * @return static
         */
        public static function from(array $properties, bool $ignoreUnmapped = true) {
            $type = new ReflectionClass(static::class);
            $object = new static();
            foreach ($properties as $key => $value) {
                $name = lcfirst(str_replace('_', '', ucwords($key, '_')));
                if ($ignoreUnmapped && !$type->hasProperty($name)) {
                    continue;
                }
                $property = $type->getProperty($name);
                $property->setAccessible(true);
                $property->setValue($object, $value);
            }
            return $object;
        }
    }
}
