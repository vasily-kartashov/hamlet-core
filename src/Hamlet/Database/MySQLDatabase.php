<?php

namespace Hamlet\Database;

use Exception;
use mysqli;

class MySQLDatabase extends Database
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function prepare(string $query): Procedure
    {
        return new MySQLProcedure($this->connection, $query);
    }

    public function startTransaction()
    {
        $success = $this->connection->autocommit(false);
        if (!$success) {
            throw new Exception($this->connection->error);
        }
        $success = $this->connection->begin_transaction();
        if (!$success) {
            throw new Exception($this->connection->error);
        }
    }

    public function commit()
    {
        $success = $this->connection->commit();
        $success = $this->connection->autocommit(true) && $success;
        if (!$success) {
            throw new Exception($this->connection->error);
        }
    }

    public function rollback()
    {
        $success = $this->connection->rollback();
        $success = $this->connection->autocommit(true) && $success;
        if (!$success) {
            throw new Exception($this->connection->error);
        }
    }
}
