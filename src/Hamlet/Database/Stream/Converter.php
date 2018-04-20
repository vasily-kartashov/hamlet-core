<?php

namespace Hamlet\Database\Stream;

use Hamlet\Database\Processing\ConverterTrait;

class Converter
{
    use ConverterTrait;

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
}
