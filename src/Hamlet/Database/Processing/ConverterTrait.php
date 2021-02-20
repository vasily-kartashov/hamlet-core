<?php

namespace Hamlet\Database\Processing;

use Hamlet\Cast\Parser\DocBlockParser;
use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

trait ConverterTrait
{
    /**
     * @param mixed $item
     * @return bool
     */
    protected function isNull($item): bool
    {
        if (\is_array($item)) {
            foreach ($item as &$value) {
                if (!$this->isNull($value)) {
                    return false;
                }
            }
            return true;
        } else {
            return $item === null;
        }
    }

    /**
     * @param array|null $row
     * @param string $type
     * @psalm-param class-string $type
     * @return mixed|null
     */
    private function instantiate($row, string $type)
    {
        if ($row === null) {
            return null;
        } elseif ($this->isNull($row)) {
            return null;
        } else {
            static $entitySubclasses = [];
            if (!isset($entitySubclasses[$type])) {
                $entitySubclasses[$type] = \is_subclass_of($type, Entity::class);
            }
            if ($entitySubclasses[$type]) {
                return $this->instantiateEntity($type, $row);
            } else {
                $object = new $type;
                foreach ($row as $key => &$value) {
                    $object->$key = $value;
                }
                return $object;
            }
        }
    }

    /**
     * @param string $typeName
     * @psalm-param class-string $typeName
     * @param array $data
     * @return object
     */
    private function instantiateEntity(string $typeName, array $data)
    {
        /**
         * @var \ReflectionClass $type
         * @var ReflectionProperty[] $properties
         * @var \ReflectionMethod|null $typeResolver
         */
        list($type, $properties, $typeResolver) = $this->getType($typeName);

        if ($typeResolver) {
            /**
             * @var \ReflectionClass $resolvedType
             * @var ReflectionProperty[] $resolvedProperties
             */
            list($resolvedType, $resolvedProperties) = $this->getType($typeResolver->invoke(null, $data));
            /**
             * @psalm-suppress ImplicitToStringCast
             */
            if ($resolvedType !== $type && !$resolvedType->isSubclassOf($type)) {
                throw new RuntimeException('Resolved type ' . $resolvedType->getName() . ' is not subclass of ' . $type->getName());
            }
            $type = $resolvedType;
            $properties = $resolvedProperties;
        }
        $object = $type->newInstanceWithoutConstructor();
        $propertiesSet = [];
        foreach ($data as $name => &$value) {
            if (!isset($properties[$name])) {
                throw new RuntimeException('Property ' . $name . ' not found in class ' . $typeName);
            }
            $propertiesSet[$name] = 1;
            assert(
                $this->assertPropertyType($type, $properties[$name], $value),
                'Property ' . $name . ' of class ' . $typeName . ' accepts ' . var_export($value, true)
            );
            $properties[$name]->setValue($object, $value);
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($properties as $name => &$_) {
            if (!isset($propertiesSet[$name])) {
                throw new RuntimeException('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }
        return $object;
    }

    /**
     * @param       string       $typeName
     * @psalm-param class-string $typeName
     * @return       array
     * @psalm-return array{0:\ReflectionClass,1:array<\ReflectionProperty>,2:\ReflectionMethod|null}
     * @psalm-suppress MoreSpecificReturnType
     */
    private function getType(string $typeName): array
    {
        /** @var \ReflectionClass[] $types */
        static $types = [];
        /** @var ReflectionProperty[][] $properties */
        static $properties = [];
        /** @var \ReflectionMethod[] $typeResolvers */
        static $typeResolvers = [];

        if (!isset($types[$typeName])) {
            $properties[$typeName] = [];
            try {
                $types[$typeName] = new ReflectionClass($typeName);
            } catch (ReflectionException $e) {
                throw new RuntimeException('Cannot load reflection information for ' . $typeName, 1, $e);
            }

            foreach ($types[$typeName]->getProperties() as &$property) {
                $property->setAccessible(true);
                $properties[$typeName][$property->getName()] = $property;
            }

            $type = $types[$typeName];
            do {
                if ($type->hasMethod('__resolveType')) {
                    try {
                        $method = $type->getMethod('__resolveType');
                    } catch (ReflectionException $e) {
                        throw new RuntimeException('Cannot access __resolveType method', 0, $e);
                    }

                    if (!$method->isStatic() || !$method->isPublic()) {
                        throw new RuntimeException('Method __resolveType must be public static method');
                    }
                    $typeResolvers[$typeName] = $method;
                }
            } while ($type = $type->getParentClass());
        }
        return [$types[$typeName], $properties[$typeName], $typeResolvers[$typeName] ?? null];
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param ReflectionProperty $reflectionProperty
     * @param mixed $value
     * @return bool
     */
    private function assertPropertyType(ReflectionClass $reflectionClass, ReflectionProperty $reflectionProperty, $value): bool
    {
        $type = DocBlockParser::fromProperty($reflectionClass, $reflectionProperty);
        return $type->matches($value);
    }
}
