<?php

namespace Hamlet\Database {

    use Exception;
    use mysqli;

    class MySQLProcedure implements Procedure {

        protected $connection;
        protected $statement;
        protected $parameters = [];

        public function __construct(mysqli $connection, string $query) {
            $this -> connection = $connection;
            $this -> statement = $connection -> prepare($query);
            if (!$this -> statement) {
                throw new Exception($this -> connection -> error);
            }
        }

        public function execute() {
            $this -> bindParameters();
            $success = $this -> statement -> execute();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            $success = $this -> statement -> close();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
        }

        public function fetch(callable $callback) {
            $row = $this -> initFetching();
            while (true) {
                $status = $this -> statement -> fetch();
                if ($status === true) {
                    $rowCopy = [];
                    foreach ($row as $key => $value) {
                        $rowCopy[$key] = $value;
                    }
                    call_user_func_array($callback, [$rowCopy]);
                } elseif (is_null($status)) {
                    break;
                } else {
                    throw new Exception($this -> connection -> error);
                }
            }
            $this->finalizeFetching();
        }

        public function fetchOne() {
            $row = $this -> initFetching();
            $status = $this -> statement -> fetch();
            $value = null;
            if ($status === true) {
                $value = $row;
            } elseif ($status === false) {
                throw new Exception($this -> connection -> error);
            }
            $this->finalizeFetching();
            return $value;
        }

        public function fetchAll() : array {
            $result = [];
            $this -> fetch(function ($row) use (&$result) {
                $result[] = $row;
            });
            return $result;
        }

        public function fetchAllWithKey(string $keyField) : array {
            $result = [];
            $this -> fetch(function ($row) use ($keyField, &$result) {
                $key = $row[$keyField];
                $result[$key] = $row;
            });
            return $result;
        }


        public function bindString(string $value) {
            $this->parameters[] = ['s', $value];
        }

        public function bindInteger(int $value) {
            $this->parameters[] = ['i', $value];
        }

        public function bindFloat(float $value) {
            $this->parameters[] = ['d', $value];
        }

        public function bindBlob(string $value) {
            $this->parameters[] = ['b', $value];
        }

        private function bindParameters() {
            if (count($this -> parameters) == 0) {
                return;
            }
            $callParameters = [];
            $types = '';
            $blobs = [];
            $callParameters[] = &$types;
            foreach ($this -> parameters as $i => $parameter) {
                $types .= $parameter[0];
                if ($parameter[0] == 'b') {
                    $nothing = null;
                    $callParameters[] = &$nothing;
                    $blobs[$i] = $parameter[1];
                } else {
                    $name = "value{$i}";
                    $$name = $parameter[1];
                    $callParameters[] = &$$name;
                }
            }
            $success = call_user_func_array([$this -> statement, 'bind_param'], $callParameters);
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            foreach ($blobs as $i => $data) {
                $success = $this -> statement -> send_long_data($i, $data);
                if (!$success) {
                    throw new Exception($this -> connection -> error);
                }
            }
        }

        private function initFetching() {
            $this -> bindParameters();
            $row = $this -> bindResult();
            $success = $this -> statement -> execute();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            $this -> statement -> store_result();
            return $row;
        }

        private function finalizeFetching() {
            $this -> statement -> free_result();
            $success = $this -> statement -> close();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
        }

        private function bindResult() {
            $metaData = $this -> statement -> result_metadata();
            if ($metaData === false) {
                throw new Exception($this -> connection -> error);
            }
            $row = [];
            $boundParameters = [];
            while ($field = $metaData -> fetch_field()) {
                $boundParameters[] = &$row[$field -> name];
            }
            $success = call_user_func_array([$this -> statement, 'bind_result'], $boundParameters);
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            return $row;
        }
    }
}