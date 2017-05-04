<?php

namespace Hamlet\Database\Processing;

class MapConverter extends Converter
{
    public function asFlattenMap(string $name): Processor
    {
        $splitter = $this->splitter;
        $records = [];
        $maps = [];
        foreach ($this->records as $record) {
            list($item, $record) = $splitter($record);
            $key = md5(serialize($record));
            if (!isset($maps[$key])) {
                $maps[$key] = [];
            }
            if (!$this->isNull($item)) {
                $maps[$key] += $item;
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => $record) {
            $records[$key][$name] = $maps[$key];
        }
        return Processor::with(array_values($records));
    }

    public function collectToFlattenMap(): array
    {
        return $this->asFlattenMap('result')
            ->unwrap('result')
            ->collectHead();
    }
}
