<?php

namespace Hamlet\Database\MySQL;

use Hamlet\Database\DatabaseException;
use mysqli;

class MySQLException extends DatabaseException
{
    /**
     * @var mysqli
     */
    private $connection;

    public function __construct(mysqli $connection)
    {
        parent::__construct($connection->error, $connection->errno);
        $this->connection = $connection;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}
