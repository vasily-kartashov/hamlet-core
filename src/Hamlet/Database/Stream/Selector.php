<?php

namespace Hamlet\Database\Stream;

use Generator;
use function strlen;
use function strpos;
use function substr;

class Selector extends Collector
{
    public function selectValue(string $field): Converter
    {
        $splitter =
            /**
             * @param array<string,mixed> $record
             * @return array{mixed,array<string,mixed>}
             */
            function (array $record) use ($field): array {
                $item = $record[$field];
                unset($record[$field]);
                return [$item, $record];
            };
        return new Converter($this->generator, $splitter);
    }

    public function selectFields(string ...$fields): Converter
    {
        $splitter =
            /**
             * @param array<string,mixed> $record
             * @return array{array<string,mixed>,array<string,mixed>}
             */
            function (array $record) use ($fields): array {
                $item = [];
                foreach ($fields as $field) {
                    $item[$field] = $record[$field];
                    unset($record[$field]);
                }
                return [$item, $record];
            };
        return new Converter($this->generator, $splitter);
    }

    public function map(string $keyField, string $valueField): MapConverter
    {
        $splitter =
            /**
             * @param array<string,mixed> $record
             * @return array{array<mixed>,array<string,mixed>}
             */
            function (array $record) use ($keyField, $valueField): array {
                $key = $record[$keyField];
                if (!is_int($key)) {
                    $key = (string) $key;
                }
                $item = [
                    $key => $record[$valueField]
                ];
                unset($record[$keyField]);
                unset($record[$valueField]);
                return [$item, $record];
            };
        return new MapConverter($this->generator, $splitter);
    }

    public function selectByPrefix(string $prefix): Converter
    {
        $cache = [];
        $length = strlen($prefix);
        $splitter =
            /**
             * @param array<string,mixed> $record
             * @return array{array<string,mixed>,array<string,mixed>}
             */
            function (array $record) use ($prefix, $length, &$cache): array {
                /**
                 * @var array<string,string|false> $cache
                 */
                $item = [];
                foreach ($record as $field => &$value) {
                    if (isset($cache[$field])) {
                        $suffix = $cache[$field];
                    } else {
                        if (strpos($field, $prefix) === 0) {
                            $suffix = substr($field, $length);
                        } else {
                            $suffix = false;
                        }
                        $cache[$field] = $suffix;
                    }
                    if ($suffix) {
                        $item[$suffix] = $value;
                        unset($record[$field]);
                    }
                }
                return [$item, $record];
            };
        return new Converter($this->generator, $splitter);
    }

    public function selectAll(): Converter
    {
        $splitter = function (array $record): array {
            return [$record, []];
        };
        return new Converter($this->generator, $splitter);
    }

    public function collate(string ...$fields): Converter
    {
        $splitter =
            /**
             * @param array<string,mixed> $record
             * @return array{mixed,array<string,mixed>}
             */
            function (array $record) use ($fields): array {
                $item = null;
                foreach ($fields as $field) {
                    if ($item === null) {
                        $item = $record[$field];
                    }
                    unset($record[$field]);
                }
                return [$item, $record];
            };
        return new Converter($this->generator, $splitter);
    }

    public function collateAll(): Collector
    {
        $generator = function (): Generator {
            foreach (($this->generator)() as list($key, $record)) {
                foreach ($record as $value) {
                    if ($value !== null) {
                        yield [$key, $value];
                        break;
                    }
                }
            }
        };
        return new Collector($generator);
    }

    public function withKey(string $keyField): Collector
    {
        $generator = function () use ($keyField): Generator {
            foreach (($this->generator)() as list($_, $record)) {
                yield [$record[$keyField], $record];
            }
        };
        return new Collector($generator);
    }
}
