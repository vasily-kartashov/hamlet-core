<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Selector as FetchSelector;
use Hamlet\Database\Stream\Selector as StreamSelector;
use Psr\Log\LoggerAwareInterface;

interface Procedure extends LoggerAwareInterface
{
    public function bindBlob(string $value): void;

    public function bindFloat(float $value): void;

    public function bindInteger(int $value): void;

    public function bindString(string $value): void;

    public function bindNullableBlob(?string $value): void;

    public function bindNullableFloat(?float $value): void;

    public function bindNullableInteger(?int $value): void;

    public function bindNullableString(?string $value): void;

    /**
     * @param array<float> $values
     */
    public function bindFloatList(array $values): void;

    /**
     * @param array<int> $values
     */
    public function bindIntegerList(array $values): void;

    /**
     * @param array<string> $values
     */
    public function bindStringList(array $values): void;

    public function insert(): int;

    public function execute(): void;

    /**
     * @return array<string,mixed>|null
     */
    public function fetchOne(): ?array;

    /**
     * @return array<array<string,mixed>>
     */
    public function fetchAll(): array;

    public function affectedRows(): int;

    public function processOne(): FetchSelector;

    public function processAll(): FetchSelector;

    public function stream(): StreamSelector;
}
