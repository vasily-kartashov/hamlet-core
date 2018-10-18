<?php

namespace Hamlet\Database;

use Hamlet\Database\MySQL\MySQLDatabase;
use Hamlet\Database\PDO\PDODatabase;
use Hamlet\Database\SQLite\SQLiteDatabase;
use mysqli;
use PDO;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SQLite3;
use Throwable;

/**
 * @suppress PhanInvalidCommentForDeclarationType
 */
abstract class Database implements LoggerAwareInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $transactionStarted = false;

    protected function __construct()
    {
        $this->logger = new NullLogger();
    }

    public static function mysql(string $host, string $user, string $password, string $database = null): Database
    {
        $connection = $database ? new mysqli($host, $user, $password, $database) : new mysqli($host, $user, $password);
        return new MySQLDatabase($connection);
    }

    public static function sqlite3(
        string $location,
        int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        string $encryptionKey = null
    ): Database {
        $connection = $encryptionKey ? new SQLite3($location, $flags, $encryptionKey) : new SQLite3($location, $flags);
        return new SQLiteDatabase($connection);
    }

    public static function pdo(string $dsn, string $username, string $password, array $options): Database
    {
        $connection = new PDO($dsn, $username, $password, $options);
        return new PDODatabase($connection);
    }

    abstract public function prepare(string $query): Procedure;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @template T
     *
     * @param callable $callable
     * @return mixed
     *
     * @psalm-param callable():T $callable
     * @psalm-return T
     *
     * @throws Throwable
     */
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

    /**
     * @template T
     *
     * @param callable $callable
     * @param int $maxAttempts
     * @return mixed
     *
     * @psalm-param callable():T $callable
     * @psalm-param int $maxAttempts
     * @psalm-return T
     *
     * @throws Throwable
     */
    public function tryWithTransaction(callable $callable, int $maxAttempts)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->withTransaction($callable);
            } catch (Throwable $e) {
                if ($attempt == $maxAttempts) {
                    throw $e;
                }
            }
        }
        throw new RuntimeException('Number of attempts must be greater than 0');
    }

    abstract public function startTransaction();

    abstract public function commit();

    abstract public function rollback();
}
