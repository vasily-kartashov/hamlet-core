<?php

namespace Hamlet\Database\Processing;

use Hamlet\Cast\Parser\DocBlockParser;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function Hamlet\Cast\_string;
use function is_array;
use function is_subclass_of;

trait ConverterTrait
{
    /**
     * @param mixed $item
     * @return bool
     */
    protected function isNull($item): bool
    {
        if (is_array($item)) {
            foreach ($item as $value) {
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
     * @template T as object
     * @param array<string,mixed>|null $row
     * @param class-string<T> $type
     * @return T|null
     */
    private function instantiate(?array $row, string $type)
    {
        if ($row === null) {
            return null;
        } elseif ($this->isNull($row)) {
            return null;
        } else {
            /**
             * @var array<string,bool> $entitySubclasses
             */
            static $entitySubclasses = [];
            if (!isset($entitySubclasses[$type])) {
                $entitySubclasses[$type] = is_subclass_of($type, Entity::class);
            }
            if ($entitySubclasses[$type]) {
                return $this->instantiateEntity($type, $row);
            } elseif (class_exists($type)) {
                /**
                 * @psalm-suppress MixedMethodCall
                 */
                $object = new $type;
                foreach ($row as $key => $value) {
                    $object->$key = $value;
                }
                return $object;
            } else {
                throw new DatabaseException('Cannot instantiate class ' . $type);
            }
        }
    }

    /**
     * @template T as object
     * @param class-string<T> $typeName
     * @param array<string,mixed> $data
     * @return T
     * @psalm-suppress InvalidReturnType
     */
    private function instantiateEntity(string $typeName, array $data): object
    {
        [$type, $properties, $typeResolver] = $this->getType($typeName);

        if ($typeResolver) {
            $resolvedTypeName = _string()->assert($typeResolver->invoke(null, $data));
            if (!class_exists($resolvedTypeName)) {
                throw new DatabaseException('Resolved type ' . $resolvedTypeName . ' does not exist');
            }
            [$resolvedType, $resolvedProperties] = $this->getType($resolvedTypeName);
            if ($resolvedType !== $type && !$resolvedType->isSubclassOf($type)) {
                throw new DatabaseException('Resolved type ' . $resolvedType->getName() . ' is not subclass of ' . $type->getName());
            }
            $type = $resolvedType;
            $properties = $resolvedProperties;
        }
        $object = $type->newInstanceWithoutConstructor();
        $propertiesSet = [];
        foreach ($data as $name => &$value) {
            if (!isset($properties[$name])) {
                throw new DatabaseException('Property ' . $name . ' not found in class ' . $typeName);
            }
            $propertiesSet[$name] = 1;
            assert(
                $this->assertPropertyType($type, $properties[$name], $value),
                'Property ' . $name . ' of class ' . $typeName . ' accepts ' . var_export($value, true)
            );
            $properties[$name]->setValue($object, $value);
        }
        foreach ($properties as $name => $_) {
            if (!array_key_exists($name, $propertiesSet)) {
                throw new DatabaseException('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }
        return $object;
    }

    /**
     * @template T
     * @param class-string<T> $typeName
     * @return array{0:ReflectionClass<T>,1:array<ReflectionProperty>,2:ReflectionMethod|null}
     * @psalm-suppress MoreSpecificReturnType
     */
    private function getType(string $typeName): array
    {
        /** @var ReflectionClass[] $types */
        static $types = [];
        /** @var ReflectionProperty[][] $properties */
        static $properties = [];
        /** @var ReflectionMethod[] $typeResolvers */
        static $typeResolvers = [];

        if (!isset($types[$typeName])) {
            $properties[$typeName] = [];
            try {
                $types[$typeName] = new ReflectionClass($typeName);
            } catch (ReflectionException $e) {
                throw new DatabaseException('Cannot load reflection information for ' . $typeName, 1, $e);
            }

            foreach ($types[$typeName]->getProperties() as $property) {
                $property->setAccessible(true);
                $properties[$typeName][$property->getName()] = $property;
            }

            $type = $types[$typeName];
            do {
                if ($type->hasMethod('__resolveType')) {
                    $method = $type->getMethod('__resolveType');
                    if (!$method->isStatic() || !$method->isPublic()) {
                        throw new DatabaseException('Method __resolveType must be public static method');
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
