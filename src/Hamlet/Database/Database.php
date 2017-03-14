<?php

namespace Hamlet\Database {

    use Exception;
    use mysqli;
    use SQLite3;

    abstract class Database {

        public static function mysql($host, $user, $password, $database = null) : Database {
            $connection = new mysqli($host, $user, $password, $database);
            return new MySQLDatabase($connection);
        }

        public static function sqlite($path) : Database {
            $connection = new SQLite3($path);
            return new SQLiteDatabase($connection);
        }

        public abstract function prepare($query);

        public abstract function startTransaction();

        public abstract function commit();
    }
}