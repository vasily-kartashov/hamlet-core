<?php

namespace Hamlet\Database\MySQL;

use Exception;
use Hamlet\Database\Database;
use Hamlet\Database\Procedure;
use mysqli;

class MySQLDatabase extends Database
{
    /**
     * @var mysqli
     */
    private $connection;

    public function __construct(mysqli $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    public function prepare(string $query): Procedure
    {
        $procedure = new MySQLProcedure($this->connection, $query);
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
        $success = $this->connection->begin_transaction();
        if (!$success) {
            $this->logger->warning($this->connection->error);
            throw new MySQLException($this->connection);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function commit()
    {
        $this->logger->debug('Committing transaction');
        $success = $this->connection->commit();
        if (!$success) {
            $this->logger->warning($this->connection->error);
            throw new MySQLException($this->connection);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function rollback()
    {
        $this->logger->debug('Rolling back transaction');
        $success = $this->connection->rollback();
        if (!$success) {
            $this->logger->warning($this->connection->error);
            throw new MySQLException($this->connection);
        }
    }
}
