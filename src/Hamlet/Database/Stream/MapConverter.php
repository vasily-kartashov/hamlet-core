<?php

namespace Hamlet\Database\Stream;

use Generator;
use function Hamlet\Cast\_list;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;

class MapConverter extends Converter
{
    public function flatten(): Collector
    {
        $generator = function (): Generator {
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
        return function () use ($name): Generator {
            $currentGroup = null;
            $lastRecord = null;
            $index = 0;
            foreach (($this->generator)() as list($_, $record)) {
                list($item, $record) = ($this->splitter)(_map(_string(), _mixed())->cast($record));
                if ($lastRecord !== $record) {
                    if ($currentGroup !== null) {
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
                    if ($currentGroup === null) {
                        $currentGroup = [];
                    }
                    $currentGroup += _list(_mixed())->assert($item);
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
