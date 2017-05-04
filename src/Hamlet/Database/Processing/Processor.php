<?php

namespace Hamlet\Database\Processing;

class Processor extends Collector
{
    public function __construct(array $records = [])
    {
        parent::__construct($records);
    }

    public static function withOne($row): Processor
    {
        return new Processor(is_null($row) ? [] : [$row]);
    }

    public static function with(array $records): Processor
    {
        return new Processor($records);
    }

    public function pickByPrefix(string $prefix): Converter
    {
        $pattern = $prefix . '_';
        $length = strlen($pattern);
        $splitter = function($record) use ($pattern, $length) {
            $item = [];
            foreach ($record as $field => $value) {
                if (substr($field, 0, $length) == $pattern) {
                    $item[substr($field, $length)] = $value;
                    unset($record[$field]);
                }
            }
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function pickOne(string $field): Converter
    {
        $splitter = function($record) use ($field) {
            $item = $record[$field];
            unset($record[$field]);
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function all(): Converter
    {
        $splitter = function($record) {
            return [$record, null];
        };
        return new Converter($this->records, $splitter);
    }

    public function pick(string... $fields): Converter
    {
        $splitter = function($record) use ($fields) {
            $item = [];
            foreach ($fields as $field) {
                $item[$field] = $record[$field];
                unset($record[$field]);
            }
            return [$item, $record];
        };
        return new Converter($this->records, $splitter);
    }

    public function map(string $keyField, string $valueField): MapConverter
    {
        $splitter = function($record) use ($keyField, $valueField) {
            $item = [
                $record[$keyField] => $record[$valueField]
            ];
            unset($record[$keyField]);
            unset($record[$valueField]);
            return [$item, $record];
        };
        return new MapConverter($this->records, $splitter);
    }

    public function collate(string... $fields): Converter
    {
        $splitter = function($record) use ($fields) {
            $item = null;
            foreach ($fields as $field) {
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
            foreach ($record as $value) {
                if ($value !== null) {
                    $records[] = $value;
                    break;
                }
            }
        }
        return new Collector($records);
    }

    public function unwrapAndCollectHead()
    {
        $head = $this->collectHead();
        return $head ? array_pop($head) : null;
    }

    public function unwrapAndCollectToList(): array
    {
        $records = [];
        foreach ($this->records as $record) {
            $records[] = array_pop($record);
        }
        return $records;
    }

    public function unwrap(string $field): Collector
    {
        $records = [];
        foreach ($this->records as $record) {
            $records[] = $record[$field];
        }
        return new Collector($records);
    }
}
