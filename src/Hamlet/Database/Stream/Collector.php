<?php

namespace Hamlet\Database\Stream;

class Collector
{
    /** @var callable */
    protected $generator;

    public function __construct(callable $generator)
    {
        $this->generator = $generator;
    }

    public function collectAll(): array
    {
        $result = [];
        foreach (($this->generator)() as list($key, $value)) {
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function collectHead()
    {
        return ($this->generator)();
    }

    /**
     * @param callable $callable
     * @psalm-param callable(mixed):void $callable
     * @return void
     */
    public function forEach(callable $callable)
    {
        foreach (($this->generator)() as list($_, $value)) {
            ($callable)($value);
        }
    }

    /**
     * @param callable $callable
     * @psalm-param callable(mixed,mixed):void $callable
     * @return void
     */
    public function forEachWithIndex(callable $callable)
    {
        foreach (($this->generator)() as list($key, $value)) {
            ($callable)($key, $value);
        }
    }
}
