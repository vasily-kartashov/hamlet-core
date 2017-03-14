<?php

namespace Hamlet\Database {

    use mysqli;
    use SQLite3;

    abstract class Database {

        public static function mysql(string $host, string $user, string $password, string $database = null) : Database {
            $connection = new mysqli($host, $user, $password, $database);
            return new MySQLDatabase($connection);
        }

        public static function sqlite(string $path) : Database {
            $connection = new SQLite3($path);
            return new SQLiteDatabase($connection);
        }

        public abstract function prepare(string $query) : Procedure;

        public abstract function startTransaction();

        public abstract function commit();

        public abstract function rollback();
    }
}