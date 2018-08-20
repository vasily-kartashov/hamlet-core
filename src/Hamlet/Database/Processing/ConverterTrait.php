<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
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
     * @param class-string $type
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
        /** @var ReflectionClass[] $types */
        static $types = [];
        /** @var \ReflectionProperty[][] $properties */
        static $properties = [];
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
        }

        // @todo deal with private constructors
        $object = new $typeName();
        $propertiesSet = [];
        foreach ($data as $name => &$value) {
            if (!isset($properties[$typeName][$name])) {
                throw new RuntimeException('Property ' . $name . ' not found in class ' . $typeName);
            }
            $propertiesSet[$name] = 1;
            $properties[$typeName][$name]->setValue($object, $value);
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($properties[$typeName] as $name => &$_) {
            if (!isset($propertiesSet[$name])) {
                throw new RuntimeException('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }
        return $object;
    }
}
