<?php

namespace Hamlet\Database;

use Exception;
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

/**
 * @suppress PhanInvalidCommentForDeclarationType
 */
abstract class Database implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $transactionStarted = false;

    /**
     * @var callable[]
     * @psalm-var array<string,callable():void>
     */
    protected $onSuccess = [];

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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @template T
     *
     * @param       callable                      $callable
     * @psalm-param callable():T                  $callable
     *
     * @param       callable[]                    $onSuccess
     * @psalm-param array<string,callable():void> $onSuccess
     *
     * @return       mixed
     * @psalm-return T
     */
    public function withTransaction(callable $callable, array $onSuccess = [])
    {
        try {
            $nested = $this->transactionStarted;
            if (!$nested) {
                $this->startTransaction();
            }
            $this->onSuccess = array_merge($this->onSuccess, $onSuccess);
            $this->transactionStarted = true;
            $result = $callable();
            if (!$nested) {
                $this->commit();
                foreach ($this->onSuccess as $callback) {
                    $callback();
                }
                $this->onSuccess = [];
            }
            $this->transactionStarted = $nested;
            return $result;
        } catch (Exception $e) {
            $this->onSuccess = [];
            if ($this->transactionStarted) {
                $this->rollback();
                $this->transactionStarted = false;
            }
            throw new RuntimeException('Exception within transaction caught', 0, $e);
        }
    }

    /**
     * @template T
     *
     * @param       callable                      $callable
     * @psalm-param callable():T                  $callable
     *
     * @param       int                           $maxAttempts
     *
     * @param       callable[]                    $onSuccess
     * @psalm-param array<string,callable():void> $onSuccess
     *
     * @return       mixed
     * @psalm-return T
     */
    public function tryWithTransaction(callable $callable, int $maxAttempts, array $onSuccess = [])
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->withTransaction($callable, $onSuccess);
            } catch (Exception $e) {
                if ($attempt == $maxAttempts) {
                    throw new RuntimeException('Exception within transaction caught', 0, $e);
                }
            }
        }
        throw new RuntimeException('Number of attempts must be greater than 0');
    }

    /**
     * @return void
     */
    abstract public function startTransaction();

    /**
     * @return void
     */
    abstract public function commit();

    /**
     * @return void
     */
    abstract public function rollback();
}
