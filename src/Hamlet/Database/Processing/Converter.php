<?php

namespace Hamlet\Database\Processing;

use Exception;
use Hamlet\Database\Entity;
use ReflectionClass;

class Converter
{
    protected $records;
    protected $splitter;

    public function __construct(array $records, callable $splitter)
    {
        $this->records = $records;
        $this->splitter = $splitter;
    }

    public function name(string $name): Selector
    {
        $records = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            $record[$name] = $item;
            $records[] = $record;
        }
        return new Selector($records);
    }

    public function group(): Collector
    {
        $records = [];
        foreach ($this->groupRecordsInto(':property:') as &$record) {
            $records[] = $record[':property:'];
        }
        return new Collector($records);
    }

    public function groupInto(string $name): Selector
    {
        return new Selector($this->groupRecordsInto($name));
    }

    private function groupRecordsInto(string $name): array
    {
        $records = [];
        $groups = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            $key = \md5(\serialize($record));
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            if (!$this->isNull($item)) {
                $groups[$key][] = $item;
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => &$record) {
            $records[$key][$name] = $groups[$key];
        }
        return \array_values($records);
    }

    public function cast(string $type): Collector
    {
        $records = [];
        foreach ($this->castRecordsInto($type, ':property:') as &$record) {
            $records[] = $record[':property:'];
        }
        return new Collector($records);
    }

    public function castInto(string $type, string $name): Selector
    {
        return new Selector($this->castRecordsInto($type, $name));
    }

    private function castRecordsInto(string $type, string $name): array
    {
        $records = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            $record[$name] = $this->instantiate($item, $type);
            $records[] = $record;
        }
        return $records;
    }

    private function instantiate($row, $type)
    {
        if ($this->isNull($row)) {
            return null;
        }
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

    protected function isNull($item): bool
    {
        if (\is_array($item)) {
            foreach ($item as &$value) {
                if ($value !== null) {
                    return false;
                }
            }
            return true;
        } else {
            return $item === null;
        }
    }

    private function instantiateEntity(string $typeName, array $data)
    {
        /** @var ReflectionClass[] $types */
        static $types = [];
        /** @var \ReflectionProperty[][] $properties */
        static $properties = [];
        if (!isset($types[$typeName])) {
            $properties[$typeName] = [];
            $types[$typeName] = new ReflectionClass($typeName);
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
                throw new Exception('Property ' . $name . ' not found in class ' . $typeName);
            }
            $propertiesSet[$name] = 1;
            $properties[$typeName][$name]->setValue($object, $value);
        }
        foreach ($properties[$typeName] as $name => &$property) {
            if (!isset($propertiesSet[$name])) {
                throw new Exception('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }
        return $object;
    }
}
