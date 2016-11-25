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
                list($reducedRow, $item) = $splitter($row);
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

        public static function commonExtractor(array $map) : callable {
            return function ($row) use ($map) {
                $common = [];
                foreach ($map as $field => $alias) {
                    if (is_int($field)) {
                        $common[$alias] = $row[$alias];
                        unset($row[$alias]);
                    } else {
                        $common[$alias] = $row[$field];
                        unset($row[$field]);
                    }
                }
                return [$common, $row];
            };
        }

        public static function varyingAtomicExtractor(string $field) : callable {
            return function ($row) use ($field) {
                $value = $row[$field];
                unset($row[$field]);
                return [$row, $value];
            };
        }

        public static function varyingExtractor(array $map) : callable {
            return function ($row) use ($map) {
                $value = [];
                foreach ($map as $field => $alias) {
                    if (is_int($field)) {
                        $value[$alias] = $row[$alias];
                        unset($row[$alias]);
                    } else {
                        $value[$alias] = $row[$field];
                        unset($row[$field]);
                    }
                }
                return [$row, $value];
            };
        }

        public function collectToList() : array {
            return $this->rows;
        }

        public function collectToAssoc(string $keyField) : array {
            $assoc = [];
            foreach ($this -> rows as $row) {
                $assoc[$row[$keyField]] = $row;
            }
            return $assoc;
        }

        public function collectToMap(string $keyField, string $valueField) : array {
            $map = [];
            foreach ($this->rows as $row) {
                $map[$row[$keyField]] = $row[$valueField];
            }
            return $map;
        }
    }
}
