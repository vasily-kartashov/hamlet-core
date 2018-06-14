<?php

namespace Hamlet\Database\Processing;

class Converter
{
    use ConverterTrait;

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
}
