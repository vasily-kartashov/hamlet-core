<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Processor;

abstract class AbstractProcedure implements Procedure
{
    protected $parameters = [];

    public function bindBlob(string $value)
    {
        $this->parameters[] = ['b', $value];
    }

    public function bindFloat(float $value)
    {
        $this->parameters[] = ['d', $value];
    }

    public function bindInteger(int $value)
    {
        $this->parameters[] = ['i', $value];
    }

    public function bindString(string $value)
    {
        $this->parameters[] = ['s', $value];
    }

    public function bindNullableBlob($value)
    {
        assert(is_null($value) || is_string($value));
        $this->parameters[] = ['b', $value];
    }

    public function bindNullableFloat($value)
    {
        assert(is_null($value) || is_float($value));
        $this->parameters[] = ['d', $value];
    }

    public function bindNullableInteger($value)
    {
        assert(is_null($value) || is_int($value));
        $this->parameters[] = ['i', $value];
    }

    public function bindNullableString($value)
    {
        assert(is_null($value) || is_string($value));
        $this->parameters[] = ['s', $value];
    }

    public function bindFloatList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_float($value));
        }
        $this->parameters[] = ['d', $values];
    }

    public function bindIntegerList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_int($value));
        }
        $this->parameters[] = ['i', $values];
    }

    public function bindStringList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_string($value));
        }
        $this->parameters[] = ['s', $values];
    }

    public function processOne(): Processor
    {
        return Processor::withOne($this->fetchOne());
    }

    public function processAll(): Processor
    {
        return Processor::with($this->fetchAll());
    }
}
