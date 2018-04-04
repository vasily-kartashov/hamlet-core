<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class Converter
{
    /** @var array */
    protected $records;

    /**
     * @var callable
     * @psalm-var callable(array<int,array>) : array<int,array>
     */
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
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($records as $key => &$_) {
            $records[$key][$name] = $groups[$key];
        }
        return \array_values($records);
    }

    /**
     * @param string $type
     * @return Collector
     */
    public function cast(string $type): Collector
    {
        $records = [];
        foreach ($this->castRecordsInto($type, ':property:') as &$record) {
            $records[] = $record[':property:'];
        }
        return new Collector($records);
    }

    /**
     * @param string $type
     * @param string $name
     * @return Selector
     */
    public function castInto(string $type, string $name): Selector
    {
        return new Selector($this->castRecordsInto($type, $name));
    }

    /**
     * @param string $type
     * @param string $name
     * @return array
     */
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

    /**
     * @param array|null $row
     * @param string $type
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
     * @param string $typeName
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
