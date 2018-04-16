<?php

namespace Hamlet\Database\Stream;

use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class Converter
{
    /** @var callable */
    protected $generator;

    /**
     * @var callable
     * @psalm-var callable(array<int,array>) : array<int,array>
     */
    protected $splitter;

    public function __construct(callable $generator, callable $splitter)
    {
        $this->generator = $generator;
        $this->splitter = $splitter;
    }

    public function name(string $name): Selector
    {
        $generator = function () use ($name) {
            foreach (($this->generator)() as list($key, $record)) {
                list($item, $record) = ($this->splitter)($record);
                $record[$name] = $item;
                yield [$key, $record];
            }
        };
        return new Selector($generator);
    }

    public function group(): Collector
    {
        $generator = function () {
            $aggregator = $this->aggregateRecordsInto(':property:');
            foreach ($aggregator() as list($key, $record)) {
                foreach ($record[':property:'] as $value) {
                    yield [$key, $value];
                }
            }
        };
        return new Collector($generator);
    }

    public function groupInto(string $name): Selector
    {
        return new Selector($this->aggregateRecordsInto($name));
    }

    protected function aggregateRecordsInto(string $name): callable
    {
        return function () use ($name) {
            $currentGroup = null;
            $lastRecord = null;
            $index = 0;
            foreach (($this->generator)() as list($key, $record)) {
                list($item, $record) = ($this->splitter)($record);
                if ($lastRecord !== $record) {
                    if (!$this->isNull($currentGroup)) {
                        $lastRecord[$name] = $currentGroup;
                        if (!$this->isNull($lastRecord)) {
                            yield [$index++, $lastRecord];
                        }
                    }
                    $currentGroup = [];
                }
                if (!$this->isNull($item)) {
                    $currentGroup[] = $item;
                }
                $lastRecord = $record;
            }
            $lastRecord[$name] = $currentGroup;
            if (!$this->isNull($lastRecord)) {
                yield [$index, $lastRecord];
            }
        };
    }

    /**
     * @param string $type
     * @return Collector
     */
    public function cast(string $type): Collector
    {
        $generator = function () use ($type) {
            $converter = $this->castRecordsInto($type, ':property:');
            foreach (($converter)() as list($key, $record)) {
                yield [$key, $record[':property:']];
            }
        };
        return new Collector($generator);
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
     * @return callable
     */
    private function castRecordsInto(string $type, string $name): callable
    {
        return function () use ($type, $name) {
            foreach (($this->generator)() as list($key, $record)) {
                list($item, $record) = ($this->splitter)($record);
                $record[$name] = $this->instantiate($item, $type);
                yield [$key, $record];
            }
        };
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
