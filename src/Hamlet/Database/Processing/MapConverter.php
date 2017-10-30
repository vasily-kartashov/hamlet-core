<?php

namespace Hamlet\Database\Processing;

class MapConverter extends Converter
{
    public function flatten(): Collector
    {
        $map = [];
        foreach ($this->flattenRecordsInto(':property:') as &$record) {
            $map += $record[':property:'];
        }
        return new Collector($map);
    }

    public function flattenInto(string $name): Selector
    {
        return new Selector($this->flattenRecordsInto($name));
    }

    private function flattenRecordsInto(string $name): array
    {
        $records = [];
        $maps = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            $key = \md5(\serialize($record));
            if (!isset($maps[$key])) {
                $maps[$key] = [];
            }
            if (!$this->isNull($item)) {
                $maps[$key] += $item;
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => &$record) {
            $records[$key][$name] = $maps[$key];
        }
        return \array_values($records);
    }
}
