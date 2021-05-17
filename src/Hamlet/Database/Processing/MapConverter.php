<?php

namespace Hamlet\Database\Processing;

use function array_values;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;
use function md5;
use function serialize;

class MapConverter extends Converter
{
    public function flatten(): Collector
    {
        $map = [];
        foreach ($this->flattenRecordsInto(':property:') as $record) {
            assert(isset($record[':property:']));
            $map += (array) $record[':property:'];
        }
        return new Collector($map);
    }

    public function flattenInto(string $name): Selector
    {
        return new Selector($this->flattenRecordsInto($name));
    }

    /**
     * @param string $name
     * @return array<array<string,mixed>>
     */
    private function flattenRecordsInto(string $name): array
    {
        $records = [];
        $maps = [];
        foreach ($this->records as &$record) {
            list($item, $record) = ($this->splitter)($record);
            $key = md5(serialize($record));
            if (!isset($maps[$key])) {
                $maps[$key] = [];
            }
            if (!$this->isNull($item)) {
                $maps[$key] += _map(_string(), _mixed())->cast($item);
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => $_) {
            $records[$key][$name] = $maps[$key];
        }
        return array_values($records);
    }
}
