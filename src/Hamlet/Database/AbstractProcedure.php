<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Selector;
use Psr\Log\LoggerInterface;
use function Hamlet\Cast\_array;
use function Hamlet\Cast\_float;
use function Hamlet\Cast\_int;
use function Hamlet\Cast\_string;

abstract class AbstractProcedure implements Procedure
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var list<array{0:'i'|'b'|'d'|'s',1:int|float|string|array<int>|array<float>|array<string>|null}>
     */
    protected $parameters = [];

    public function bindBlob(string $value): void
    {
        $this->parameters[] = ['b', $value];
    }

    public function bindFloat(float $value): void
    {
        $this->parameters[] = ['d', $value];
    }

    public function bindInteger(int $value): void
    {
        $this->parameters[] = ['i', $value];
    }

    public function bindString(string $value): void
    {
        $this->parameters[] = ['s', $value];
    }

    public function bindNullableBlob(?string $value): void
    {
        $this->parameters[] = ['b', $value];
    }

    public function bindNullableFloat(?float $value): void
    {
        $this->parameters[] = ['d', $value];
    }

    public function bindNullableInteger(?int $value): void
    {
        $this->parameters[] = ['i', $value];
    }

    public function bindNullableString(?string $value): void
    {
        $this->parameters[] = ['s', $value];
    }

    public function bindFloatList(array $values): void
    {
        _array(_float())->assert($values);
        $this->parameters[] = ['d', $values];
    }

    public function bindIntegerList(array $values): void
    {
        _array(_int())->assert($values);
        $this->parameters[] = ['i', $values];
    }

    public function bindStringList(array $values): void
    {
        _array(_string())->assert($values);
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
