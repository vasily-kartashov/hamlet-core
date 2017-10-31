<?php

namespace Hamlet\Database\Processing;

class Selector extends Collector
{
    public function selectValue(string $field): Converter
    {
        $splitter = function (array $record) use ($field): array {
            $item = $record[$field];
            unset($record[$field]);
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function selectFields(string... $fields): Converter
    {
        $splitter = function (array $record) use ($fields): array {
            $item = [];
            foreach ($fields as &$field) {
                $item[$field] = $record[$field];
                unset($record[$field]);
            }
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function map(string $keyField, string $valueField): MapConverter
    {
        $splitter = function (array $record) use ($keyField, $valueField): array {
            $item = [
                $record[$keyField] => $record[$valueField]
            ];
            unset($record[$keyField]);
            unset($record[$valueField]);
            return [$item, $record];
        };
        return new MapConverter($this->records, $splitter);
    }

    public function selectByPrefix(string $prefix): Converter
    {
        $length = \strlen($prefix);
        $splitter = function (array $record) use ($prefix, $length): array {
            $item = [];
            foreach ($record as $field => &$value) {
                if (\substr($field, 0, $length) == $prefix) {
                    $item[\substr($field, $length)] = $value;
                    unset($record[$field]);
                }
            }
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function selectAll(): Converter
    {
        $splitter = function (array $record): array {
            return [$record, null];
        };
        return new Converter($this->records, $splitter);
    }

    public function collate(string... $fields): Converter
    {
        $splitter = function (array $record) use ($fields): array {
            $item = null;
            foreach ($fields as &$field) {
                if ($item === null) {
                    $item = $record[$field];
                }
                unset($record[$field]);
            }
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function collateAll(): Collector
    {
        $records = [];
        foreach ($this->records as $record) {
            foreach ($record as &$value) {
                if ($value !== null) {
                    $records[] = $value;
                    break;
                }
            }
        }
        return new Collector($records);
    }

    public function withKey(string $keyField): Collector
    {
        $records = [];
        foreach ($this->records as &$record) {
            $records[$record[$keyField]] = $record;
        }
        return new Collector($records);
    }
}
