<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Selector;
use Psr\Log\LoggerInterface;

abstract class AbstractProcedure implements Procedure
{
    /** @var LoggerInterface|null */
    protected $logger;

    /** @var array[]  */
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
        assert($value === null || is_string($value));
        $this->parameters[] = ['b', $value];
    }

    public function bindNullableFloat($value)
    {
        assert($value === null || is_float($value));
        $this->parameters[] = ['d', $value];
    }

    public function bindNullableInteger($value)
    {
        assert($value === null || is_int($value));
        $this->parameters[] = ['i', $value];
    }

    public function bindNullableString($value)
    {
        assert($value === null || is_string($value));
        $this->parameters[] = ['s', $value];
    }

    public function bindFloatList(array $values)
    {
        assert(!empty($values));
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            assert(is_float($value));
        }
        $this->parameters[] = ['d', $values];
    }

    public function bindIntegerList(array $values)
    {
        assert(!empty($values));
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            assert(is_int($value));
        }
        $this->parameters[] = ['i', $values];
    }

    public function bindStringList(array $values)
    {
        assert(!empty($values));
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            assert(is_string($value));
        }
        $this->parameters[] = ['s', $values];
    }

    public function processOne(): Selector
    {
        $record = $this->fetchOne();
        return new Selector($record ? [$record] : []);
    }

    public function processAll(): Selector
    {
        return new Selector($this->fetchAll());
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
