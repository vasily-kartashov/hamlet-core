<?php

namespace Hamlet\Database;

interface ProcedureInterface
{
    /**
     * Bind blob value
     *
     * @param string $value
     */
    public function bindBlob($value);

    /**
     * Bind float value
     *
     * @param float $value
     */
    public function bindFloat($value);

    /**
     * Bind int value
     *
     * @param int $value
     */
    public function bindInteger($value);

    /**
     * Bind string value
     *
     * @param string $value
     */
    public function bindString($value);

    /**
     * Execute statement
     */
    public function execute();

    /**
     * @param callable $callback
     */
    public function fetch(callable $callback);

    /**
     * @return array[]
     */
    public function fetchAll();

    /**
     * @param string $keyField
     *
     * @return array[]
     */
    public function fetchAllWithKey($keyField);

    /**
     * @return array
     */
    public function fetchOne();
}
