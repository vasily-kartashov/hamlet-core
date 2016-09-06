<?php

namespace Hamlet\Database {

    use Exception;
    use mysqli;
    use SQLite3;

    class Database {

        private $connection;
        private $type;

        private function __construct($connection, $type) {
            $this -> connection = $connection;
            $this -> type = $type;
        }

        public static function mysql($host, $user, $password, $database = null) {
            $connection = new mysqli($host, $user, $password, $database);
            return new Database($connection, 'mysql');
        }

        public static function sqlite($path) {
            $connection = new SQLite3($path);
            return new Database($connection, 'sqlite');
        }

        public function prepare($query) {
            switch ($this -> type) {
                case 'mysql':
                    return new MySQLProcedure($this -> connection, $query);
                case 'sqlite':
                    return new SQLiteProcedure($this -> connection, $query);
                default:
                    throw new Exception("Unsupported database type {$this -> type}");
            }
        }
    }
}