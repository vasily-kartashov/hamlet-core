<?php

namespace Hamlet\Database\MySQL;

use mysqli;
use RuntimeException;

class MySQLException extends RuntimeException
{
    public function __construct(mysqli $connection)
    {
        parent::__construct($connection->error, $connection->errno);
    }
}
