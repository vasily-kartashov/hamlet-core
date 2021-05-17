<?php

namespace Hamlet\Database\Processing;

use function array_values;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;
use function md5;
use function serialize;

class Converter
{
    use ConverterTrait;

    /**
     * @var array<array<string,mixed>>
     */
    protected $records;

    /**
     * @var callable(array<string,mixed>):array{0:mixed,1:array<string,mixed>}
     */
    protected $splitter;

    /**
     * @param array<array<string,mixed>> $records
     * @param callable(array<string,mixed>):array{0:mixed,1:array<string,mixed>} $splitter
     */
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
        foreach ($this->groupRecordsInto(':property:') as $record) {
            assert(array_key_exists(':property:', $record));
            $records[] = $record[':property:'];
        }
        return new Collector($records);
    }

    public function groupInto(string $name): Selector
    {
        return new Selector($this->groupRecordsInto($name));
    }

    /**
     * @param string $name
     * @return array<array<string,mixed>>
     */
    private function groupRecordsInto(string $name): array
    {
        $records = [];
        $groups = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            $key = md5(serialize($record));
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            if (!$this->isNull($item)) {
                $groups[$key][] = $item;
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => $_) {
            $records[$key][$name] = $groups[$key];
        }
        return array_values($records);
    }

    /**
     * @template T as object
     * @param class-string<T> $type
     * @param bool $jsonDecode
     * @return Collector
     */
    public function cast(string $type, bool $jsonDecode = false): Collector
    {
        $records = [];
        foreach ($this->castRecordsInto($type, ':property:', $jsonDecode) as $record) {
            assert(array_key_exists(':property:', $record));
            $records[] = $record[':property:'];
        }
        return new Collector($records);
    }

    /**
     * @template T as object
     * @param class-string<T> $type
     * @param string $name
     * @param bool $jsonDecode
     * @return Selector
     */
    public function castInto(string $type, string $name, bool $jsonDecode = false): Selector
    {
        return new Selector($this->castRecordsInto($type, $name, $jsonDecode));
    }

    /**
     * @template T
     * @param class-string<T> $type
     * @param string $name
     * @param bool $jsonDecode
     * @return array<array<string,mixed>>
     */
    private function castRecordsInto(string $type, string $name, bool $jsonDecode): array
    {
        $records = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            if ($jsonDecode) {
                $item = json_decode((string) $item, true);
            }
            $record[$name] = $this->instantiate(_map(_string(), _mixed())->cast($item), $type);
            $records[] = $record;
        }
        return $records;
    }
}
