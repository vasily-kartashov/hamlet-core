<?php

namespace Hamlet\Database;

use Exception;
use SQLite3;

class SQLiteDatabase extends Database
{

    private $connection;

    public function __construct(SQLite3 $connection)
    {
        $this->connection = $connection;
    }

    public function prepare(string $query): Procedure
    {
        return new SQLiteProcedure($this->connection, $query);
    }

    public function startTransaction()
    {
        $success = $this->connection->exec("BEGIN TRANSACTION");
        if (!$success) {
            throw new Exception($this->connection->lastErrorMsg());
        }
    }

    public function commit()
    {
        $success = $this->connection->exec("COMMIT");
        if (!$success) {
            throw new Exception($this->connection->lastErrorMsg());
        }
    }

    public function rollback()
    {
        $success = $this->connection->exec("ROLLBACK");
        if (!$success) {
            throw new Exception($this->connection->lastErrorMsg());
        }
    }
}
