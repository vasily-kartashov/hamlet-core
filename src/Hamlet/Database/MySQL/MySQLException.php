<?php

namespace Hamlet\Database\MySQL;

use mysqli;
use RuntimeException;

class MySQLException extends RuntimeException
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
