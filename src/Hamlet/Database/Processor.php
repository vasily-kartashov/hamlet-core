<?php

namespace Hamlet\Database {

    class Processor {

        private $rows;

        private function __construct() {}

        public static function withOne($row) : Processor {
            return Processor::with(is_null($row) ? [] : [$row]);
        }

        public static function with(array $rows) : Processor {
            $processor = new Processor();
            $processor->rows = $rows;
            return $processor;
        }

        public function group(string $title, callable $splitter) {
            $processedRows = [];
            $groups = [];
            foreach ($this -> rows as $row) {
                list($reducedRow, $item, $map) = $splitter($row);
                $key = md5(serialize($reducedRow));
                if (!isset($groups[$key])) {
                    $groups[$key] = [];
                }
                if (!$this->isNull($item)) {
                    if ($map) {
                        $groups[$key] += $item;
                    } else {
                        $groups[$key][] = $item;
                    }
                }
                $processedRows[$key] = $reducedRow;
            }
            foreach (array_keys($processedRows) as $key) {
                $processedRows[$key][$title] = $groups[$key];
            }
            return Processor::with(array_values($processedRows));
        }

        public function wrap(string $title, callable $splitter) {
            $processedRows = [];
            foreach ($this -> rows as $row) {
                list($reducedRow, $embeddedObject) = $splitter($row);
                $reducedRow[$title] = $embeddedObject;
                $processedRows[] = $reducedRow;
            }
            return Processor::with($processedRows);
        }

        public function map(string $property, callable $converter) {
            $processedRows = [];
            foreach ($this -> rows as $row) {
                $row[$property] = $converter($row[$property]);
                $processedRows[] = $row;
            }
            return Processor::with($processedRows);
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
                return [$common, $row, false];
            };
        }

        public static function mapExtractor(string $keyField, string $valueField) : callable {
            return function ($row) use ($keyField, $valueField) {
                $value = [
                    $row[$keyField] => $row[$valueField]
                ];
                unset($row[$keyField]);
                unset($row[$valueField]);
                return [$row, $value, true];
            };
        }

        public static function varyingAtomicExtractor(string $field) : callable {
            return function ($row) use ($field) {
                $value = $row[$field];
                unset($row[$field]);
                return [$row, $value, false];
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
                return [$row, $value, false];
            };
        }

        public static function varyingExtractorByPrefix(string $prefix) : callable {
            $prefixLength = strlen($prefix);
            return function ($row) use ($prefix, $prefixLength) {
                $sub = [];
                foreach ($row as $key => $value) {
                    if (substr($key, 0, $prefixLength) == $prefix) {
                        $sub[substr($key, $prefixLength)] = $value;
                        unset($row[$key]);
                    }
                }
                return [$row, $sub, false];
            };
        }

        public function collectToList() : array {
            return $this->rows;
        }

        public function collectHead() : array {
            return $this -> rows[0] ?? null;
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

        private function isNull($item) : bool {
            if (is_array($item)) {
                foreach ($item as $value) {
                    if (!is_null($value)) {
                        return false;
                    }
                }
                return true;
            } else {
                return is_null($item);
            }
        }
    }
}
