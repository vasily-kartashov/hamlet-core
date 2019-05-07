<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Selector as FetchSelector;
use Hamlet\Database\Stream\Selector as StreamSelector;
use Psr\Log\LoggerAwareInterface;

interface Procedure extends LoggerAwareInterface
{
    /**
     * @param string $value
     * @return void
     */
    public function bindBlob(string $value);

    /**
     * @param float $value
     * @return void
     */
    public function bindFloat(float $value);

    /**
     * @param int $value
     * @return void
     */
    public function bindInteger(int $value);

    /**
     * @param string $value
     * @return void
     */
    public function bindString(string $value);

    /**
     * @param string|null $value
     * @return void
     */
    public function bindNullableBlob($value);

    /**
     * @param float|null $value
     * @return void
     */
    public function bindNullableFloat($value);

    /**
     * @param int|null $value
     * @return void
     */
    public function bindNullableInteger($value);

    /**
     * @param string|null $value
     * @return void
     */
    public function bindNullableString($value);

    /**
     * @param float[] $values
     * @return void
     */
    public function bindFloatList(array $values);

    /**
     * @param int[] $values
     * @return void
     */
    public function bindIntegerList(array $values);

    /**
     * @param string[] $values
     * @return void
     */
    public function bindStringList(array $values);

    /**
     * @return int
     */
    public function insert();

    /**
     * @return void
     */
    public function execute();

    /**
     * @return array|null
     */
    public function fetchOne();

    public function fetchAll(): array;

    public function affectedRows(): int;

    public function processOne(): FetchSelector;

    public function processAll(): FetchSelector;

    public function stream(): StreamSelector;
}
