<?php

namespace Hamlet\Database {

    use \SQLite3;

    class SQLiteProcedure implements Procedure {

        private $connection;
        private $statement;
        private $parameters = [];

        public function __construct(SQLite3 $connection, string $query) {
            $this -> connection = $connection;
            $this -> statement = $connection -> prepare($query);
        }

        public function bindBlob(string $value) {
            $this -> parameters[] = [SQLITE3_BLOB, $value];
        }

        public function bindFloat(float $value) {
            $this -> parameters[] = [SQLITE3_FLOAT, $value];
        }

        public function bindInteger(int $value) {
            $this -> parameters[] = [SQLITE3_INTEGER, $value];
        }

        public function bindString(string $value) {
            $this -> parameters[] = [SQLITE3_TEXT, $value];
        }

        private function bindParameters() {
            foreach ($this -> parameters as $i => $parameter) {
                $this -> statement -> bindValue($i + 1, $parameter[1], $parameter[0]);
            }
        }

        public function execute() : void {
            $this -> bindParameters();
            $this -> statement -> execute();
        }

        public function fetch(callable $callback) {
            $this -> bindParameters();
            $result = $this -> statement -> execute();
            while (($row = $result -> fetchArray(SQLITE3_ASSOC)) !== false) {
                call_user_func_array($callback, [$row]);
            }
        }

        public function fetchAll() : array {
            $this -> bindParameters();
            $result = $this -> statement -> execute();
            $data = [];
            while (($row = $result -> fetchArray(SQLITE3_ASSOC)) !== false) {
                $data[] = $row;
            }
            return $data;
        }

        public function fetchAllWithKey(string $keyField) : array {
            $this -> bindParameters();
            $result = $this -> statement -> execute();
            $data = [];
            while (($row = $result -> fetchArray(SQLITE3_ASSOC)) !== false) {
                $key = $row[$keyField];
                unset($row[$keyField]);
                $data[$key] = $row;
            }
            return $data;
        }

        public function fetchOne() : array {
            $this -> bindParameters();
            $result = $this -> statement -> execute();
            return $result -> fetchArray(SQLITE3_ASSOC);
        }
    }
}
