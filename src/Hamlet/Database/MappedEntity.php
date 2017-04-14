<?php

namespace Hamlet\Database {

    use ReflectionClass;

    abstract class MappedEntity {

        /**
         * @param array $properties
         * @return static
         */
        public static function from(array $properties) {
            $type = new ReflectionClass(static::class);
            $object = new static();
            foreach ($properties as $key => $value) {
                $name = lcfirst(str_replace('_', '', ucwords($key, '_')));
                $property = $type->getProperty($name);
                $property->setAccessible(true);
                $property->setValue($object, $value);
            }
            return $object;
        }
    }
}
