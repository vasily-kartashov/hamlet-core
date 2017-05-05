<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Selector;

interface Procedure
{
    public function bindBlob(string $value);

    public function bindFloat(float $value);

    public function bindInteger(int $value);

    public function bindString(string $value);

    public function bindNullableBlob($value);

    public function bindNullableFloat($value);

    public function bindNullableInteger($value);

    public function bindNullableString($value);

    public function bindFloatList(array $values);

    public function bindIntegerList(array $values);

    public function bindStringList(array $values);

    public function insert();

    public function execute();

    public function fetchOne();

    public function fetchAll(): array;

    public function affectedRows(): int;

    public function processOne(): Selector;

    public function processAll(): Selector;
}
