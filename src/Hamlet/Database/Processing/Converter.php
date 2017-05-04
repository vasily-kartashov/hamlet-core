<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Entity;
use PHPUnit\Runner\Exception;
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

    public function asList(string $name): Processor
    {
        $splitter = $this->splitter;
        $records = [];
        $groups = [];
        foreach ($this->records as $record) {
            list($item, $record) = $splitter($record);
            $key = md5(serialize($record));
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            if (!$this->isNull($item)) {
                $groups[$key][] = $item;
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => $record) {
            $records[$key][$name] = $groups[$key];
        }
        return Processor::with(array_values($records));
    }

    public function asObject(string $name, string $type): Processor
    {
        $splitter = $this->splitter;
        $records = [];
        foreach ($this->records as $record) {
            list($item, $record) = $splitter($record);
            $record[$name] = $this->cast($item, $type);
            $records[] = $record;
        }
        return Processor::with($records);
    }

    public function asField(string $name): Processor
    {
        $splitter = $this->splitter;
        $records = [];
        foreach ($this->records as $record) {
            list($item, $record) = $splitter($record);
            $record[$name] = $item;
            $records[] = $record;
        }
        return Processor::with($records);
    }

    private function cast($row, $type)
    {
        if ($this->isNull($row)) {
            return null;
        }
        if (is_null($type)) {
            return $row;
        }
        if (is_subclass_of($type, Entity::class)) {
            return $this->construct($type, $row);
        } else {
            $object = new $type;
            foreach ($row as $key => $value) {
                $object->$key = $value;
            }
            return $object;
        }
    }

    protected function isNull($item): bool
    {
        if (is_array($item)) {
            foreach ($item as $value) {
                if (!is_null($value)) {
                    return false;
                }
            }
            return true;
        } else {
            return is_null($item);
        }
    }

    private function construct(string $typeName, array $data)
    {
        $type = new ReflectionClass($typeName);
        $object = new $typeName();

        foreach ($data as $name => $value) {
            if (!$type->hasProperty($name)) {
                throw new Exception('Property ' . $name . ' not found in class ' . $typeName);
            }
            $property = $type->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
        foreach ($type->getProperties() as $property) {
            if (!array_key_exists($property->getName(), $data)) {
                throw new Exception('Property ' . $typeName . '::' . $property->getName()
                    . ' not set in ' . json_encode($data));
            }
        }
        return $object;
    }
}
