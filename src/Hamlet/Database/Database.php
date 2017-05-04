<?php

namespace Hamlet\Database;

use Hamlet\Database\MySQL\MySQLDatabase;
use mysqli;

abstract class Database
{
    protected $logger;

    public static function mysql(string $host, string $user, string $password, string $database = null): Database {
        $connection = new mysqli($host, $user, $password, $database);
        return new MySQLDatabase($connection);
    }

    public abstract function prepare(string $query): Procedure;

    public abstract function startTransaction();

    public abstract function commit();

    public abstract function rollback();
}
