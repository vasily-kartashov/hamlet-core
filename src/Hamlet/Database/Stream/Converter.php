<?php

namespace Hamlet\Database\Stream;

use Generator;
use Hamlet\Database\Processing\ConverterTrait;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;

class Converter
{
    use ConverterTrait;

    /**
     * @var callable():iterable<array>
     */
    protected $generator;

    /**
     * @var callable(array<string,mixed>):array{0:mixed,1:array<string,mixed>}
     */
    protected $splitter;

    /**
     * @param callable():iterable<array> $generator
     * @param callable(array<string,mixed>):array{mixed,array<string,mixed>} $splitter
     */
    public function __construct(callable $generator, callable $splitter)
    {
        $this->generator = $generator;
        $this->splitter = $splitter;
    }

    public function name(string $name): Selector
    {
        $generator = function () use ($name): Generator {
            foreach (($this->generator)() as list($key, $record)) {
                list($item, $record) = ($this->splitter)(_map(_string(), _mixed())->cast($record));
                $record[$name] = $item;
                yield [$key, $record];
            }
        };
        return new Selector($generator);
    }

    public function group(): Collector
    {
        $generator = function (): Generator {
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
        return function () use ($name): Generator {
            $currentGroup = null;
            $lastRecord = null;
            $index = 0;
            foreach (($this->generator)() as [$_, $record]) {
                list($item, $record) = ($this->splitter)(_map(_string(), _mixed())->cast($record));
                if ($lastRecord !== $record) {
                    if (!$this->isNull($currentGroup)) {
                        if ($lastRecord === null) {
                            $lastRecord = [];
                        }
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
     * @template T as object
     * @param class-string<T> $type
     * @param bool $jsonDecode
     * @return Collector
     */
    public function cast(string $type, bool $jsonDecode = false): Collector
    {
        $generator = function () use ($type, $jsonDecode): Generator {
            $converter = $this->castRecordsInto($type, ':property:', $jsonDecode);
            foreach (($converter)() as list($key, $record)) {
                yield [$key, $record[':property:']];
            }
        };
        return new Collector($generator);
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
     * @template T as object
     * @param class-string<T> $type
     * @param string $name
     * @param bool $jsonDecode
     * @return callable
     */
    private function castRecordsInto(string $type, string $name, bool $jsonDecode): callable
    {
        return function () use ($type, $name, $jsonDecode): Generator {
            foreach (($this->generator)() as [$key, $record]) {
                list($item, $record) = ($this->splitter)(_map(_string(), _mixed())->cast($record));
                if ($jsonDecode) {
                    $item = json_decode((string) $item, true);
                }
                $record[$name] = $this->instantiate(_map(_string(), _mixed())->cast($item), $type);
                yield [$key, $record];
            }
        };
    }
}
