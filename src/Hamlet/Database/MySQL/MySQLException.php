<?php

namespace Hamlet\Database\MySQL;

use mysqli;
use Exception;

class MySQLException extends Exception
{
    public function __construct(mysqli $connection)
    {
        parent::__construct($connection->error ?? 'Unknown error', $connection->errno ?? -1);
    }
}
