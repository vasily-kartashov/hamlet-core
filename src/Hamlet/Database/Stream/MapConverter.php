<?php

namespace Hamlet\Database\Stream;

class MapConverter extends Converter
{
    public function flatten(): Collector
    {
        $generator = function () {
            $aggregator = $this->aggregateRecordsInto(':property:');
            foreach ($aggregator() as list($_, $record)) {
                foreach ($record[':property:'] as $key => $value) {
                    yield [$key, $value];
                }
            }
        };

        return new Collector($generator);
    }

    public function flattenInto(string $name): Selector
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
                    if ($currentGroup !== null) {
                        $lastRecord[$name] = $currentGroup;
                        if (!$this->isNull($lastRecord)) {
                            yield [$index++, $lastRecord];
                        }
                    }
                    $currentGroup = [];
                }
                if (!$this->isNull($item)) {
                    /** @psalm-suppress PossiblyNullOperand */
                    $currentGroup += $item;
                }
                $lastRecord = $record;
            }
            $lastRecord[$name] = $currentGroup;
            if (!$this->isNull($lastRecord)) {
                yield [$index, $lastRecord];
            }
        };
    }
}
