<?php

namespace Hamlet\Database {

    class Processor {

        private $rows;

        private function __construct() {}

        public static function with(array $rows) : Processor {
            $processor = new Processor();
            $processor->rows = $rows;
            return $processor;
        }

        public function group(string $title, callable $splitter) {
            $processedRows = [];
            $groups = [];
            foreach ($this -> rows as $row) {
                list($item, $reducedRow) = $splitter($row);
                $key = md5(serialize($reducedRow));
                if (!isset($groups[$key])) {
                    $groups[$key] = [];
                }
                $groups[$key][] = $item;
                $processedRows[$key] = $reducedRow;
            }
            foreach (array_keys($processedRows) as $key) {
                $processedRows[$key][$title] = $groups[$key];
            }
            return Processor::with(array_values($processedRows));
        }

        public static function fieldExtractor($field) {
            return function ($row) use ($field) {
                $value = $row[$field];
                unset($row[$field]);
                return [$value, $row];
            };
        }

        public static function tailMapper(array $map) {
            return function ($row) use ($map) {
                $value = [];
                foreach ($map as $field => $alias) {
                    $value[$alias] = $row[$field];
                    unset($row[$field]);
                }
                return [$value, $row];
            };
        }

        public static function groupFieldsExtractor(... $fields) {
            return function ($row) use ($fields) {
                $head = [];
                foreach ($fields as $field) {
                    $head[$field] = $row[$field];
                    unset($row[$field]);
                }
                return [$row, $head];
            };
        }

        public function collect() {
            return $this->rows;
        }

        public function collectToMap($keyField, $valueField) {
            $map = [];
            foreach ($this->rows as $row) {
                $map[$row[$keyField]] = $row[$valueField];
            }
            return $map;
        }
    }
}
