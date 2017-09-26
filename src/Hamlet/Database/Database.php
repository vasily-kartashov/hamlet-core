<?php

namespace Hamlet\Database;

use Hamlet\Database\MySQL\MySQLDatabase;
use Hamlet\Database\SQLite\SQLiteDatabase;
use Hamlet\Database\PDO\PDODatabase;
use mysqli;
use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SQLite3;
use Throwable;

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

    public static function sqlite3(
        string $location,
        $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        $encryptionKey = null
    ): Database {
        $connection = new SQLite3($location, $flags, $encryptionKey);
        return new SQLiteDatabase($connection);
    }

    public static function pdo(string $dsn, string $username = null, string $password = null, array $options = [])
    {
        $connection = new PDO($dsn, $username, $password, $options);
        return new PDODatabase($connection);
    }

    abstract public function prepare(string $query): Procedure;

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
        } catch (Throwable $e) {
            if ($this->transactionStarted) {
                $this->rollback();
                $this->transactionStarted = false;
            }
            throw $e;
        }
    }

    abstract public function startTransaction();

    abstract public function commit();

    abstract public function rollback();
}
