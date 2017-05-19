<?php

namespace Hamlet\Database;

use Exception;
use Hamlet\Database\MySQL\MySQLDatabase;
use mysqli;

abstract class Database
{
    protected $logger;
    protected $transactionStarted = false;

    public static function mysql(string $host, string $user, string $password, string $database = null): Database {
        $connection = new mysqli($host, $user, $password, $database);
        return new MySQLDatabase($connection);
    }

    public abstract function prepare(string $query): Procedure;

    public abstract function startTransaction();

    public abstract function commit();

    public abstract function rollback();

    public function withTransaction(callable $callable)
    {
        try {
            $nested = $this->transactionStarted;
            if (!$nested) {
                $this->startTransaction();
            }
            $this->transactionStarted = true;
            $callable();
            if (!$nested) {
                $this->commit();
            }
            $this->transactionStarted = $nested;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
