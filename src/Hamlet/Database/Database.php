<?php

namespace Hamlet\Database;

use Exception;
use Hamlet\Database\MySQL\MySQLDatabase;
use Hamlet\Database\SQLite\SQLiteDatabase;
use mysqli;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SQLite3;

abstract class Database implements LoggerAwareInterface
{
    protected $logger;
    protected $transactionStarted = false;

    protected function __construct()
    {
        $this->logger = new NullLogger();
    }

    public static function mysql(string $host, string $user, string $password, string $database = null): Database
    {
        $connection = new mysqli($host, $user, $password, $database);
        return new MySQLDatabase($connection);
    }

    public static function sqlite3(string $location, $flags = null, $encryptionKey = null): Database
    {
        $connection = new SQLite3($location, $flags, $encryptionKey);
        return new SQLiteDatabase($connection);
    }

    public abstract function prepare(string $query): Procedure;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function withTransaction(callable $callable)
    {
        try {
            $nested = $this->transactionStarted;
            if (!$nested) {
                $this->startTransaction();
            }
            $this->transactionStarted = true;
            $result = $callable();
            if (!$nested) {
                $this->commit();
            }
            $this->transactionStarted = $nested;
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public abstract function startTransaction();

    public abstract function commit();

    public abstract function rollback();
}
