<?php

namespace Hamlet\Database\SQLite;

use Exception;
use Hamlet\Database\Database;
use Hamlet\Database\Procedure;
use SQLite3;

class SQLiteDatabase extends Database
{
    /** @var SQLite3 */
    private $connection;

    public function __construct(SQLite3 $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    public function prepare(string $query): Procedure
    {
        $procedure = new SQLiteProcedure($this->connection, $query);
        $procedure->setLogger($this->logger);
        return $procedure;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function startTransaction()
    {
        $this->logger->debug('Starting transaction');
        $success = $this->connection->exec('BEGIN TRANSACTION');
        if (!$success) {
            throw new Exception($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function commit()
    {
        $this->logger->debug('Committing transaction');
        $success = $this->connection->exec('COMMIT');
        if (!$success) {
            throw new Exception($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function rollback()
    {
        $this->logger->debug('Rolling back transaction');
        $success = $this->connection->exec('ROLLBACK');
        if (!$success) {
            throw new Exception($this->connection->lastErrorMsg(), $this->connection->lastErrorCode());
        }
    }
}
