<?php

namespace Hamlet\Database {

    use Exception;
    use mysqli;
    use mysqli_stmt;

    class MySQLProcedure implements Procedure {

        protected $connection;
        protected $query;
        protected $parameters = [];

        public function __construct(mysqli $connection, string $query) {
            $this -> connection = $connection;
            $this -> query = $query;
        }

        public function execute() {
            $statement = $this -> bindParameters();
            $success = $statement -> execute();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            $success = $statement -> close();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
        }

        public function fetch(callable $callback) {
            /** @var mysqli_stmt $statement */
            list($row, $statement) = $this -> initFetching();
            while (true) {
                $status = $statement -> fetch();
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
            $this->finalizeFetching($statement);
        }

        public function fetchOne() {
            /** @var mysqli_stmt $statement */
            list($row, $statement) = $this -> initFetching();
            $status = $statement -> fetch();
            $value = null;
            if ($status === true) {
                $value = $row;
            } elseif ($status === false) {
                throw new Exception($this -> connection -> error);
            }
            $this->finalizeFetching($statement);
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

        public function bindBlob(string $value) {
            $this -> parameters[] = ['b', $value];
        }

        public function bindFloat(float $value) {
            $this -> parameters[] = ['d', $value];
        }

        public function bindInteger(int $value) {
            $this -> parameters[] = ['i', $value];
        }

        public function bindString(string $value) {
            $this -> parameters[] = ['s', $value];
        }

        public function bindNullableBlob($value) {
            assert(is_null($value) || is_string($value));
            $this -> parameters[] = ['b', $value];
        }

        public function bindNullableFloat($value) {
            assert(is_null($value) || is_float($value));
            $this -> parameters[] = ['d', $value];
        }

        public function bindNullableInteger($value) {
            assert(is_null($value) || is_int($value));
            $this -> parameters[] = ['i', $value];
        }

        public function bindNullableString($value) {
            assert(is_null($value) || is_string($value));
            $this -> parameters[] = ['s', $value];
        }

        public function bindFloatList(array $values) {
            assert(!empty($values));
            foreach ($values as $value) {
                assert(is_float($value));
            }
            $this -> parameters[] = ['d', $values];
        }

        public function bindIntegerList(array $values) {
            assert(!empty($values));
            foreach ($values as $value) {
                assert(is_int($value));
            }
            $this -> parameters[] = ['i', $values];
        }

        public function bindStringList(array $values) {
            assert(!empty($values));
            foreach ($values as $value) {
                assert(is_string($value));
            }
            $this -> parameters[] = ['s', $values];
        }

        public function affectedRows(): int {
            return $this -> connection -> affected_rows;
        }

        private function bindParameters() : mysqli_stmt {
            $query = $this -> query;

            // expand list parameters
            if (!empty($this -> parameters)) {
                $position = 0;
                $counter = 0;
                while (true) {
                    $position = strpos($query, '?', $position);
                    if ($position === false) {
                        break;
                    }
                    $value = $this->parameters[$counter++][1];
                    if (is_array($value)) {
                        $in = '(' . join(', ', array_fill(0, count($value), '?')) . ')';
                        $query = substr($query, 0, $position) . $in . substr($query, $position + 1);
                        $position += strlen($in);
                    } else {
                        $position++;
                    }
                }
            }
            $statement = $this -> connection -> prepare($query);
            if (!$statement) {
                throw new Exception($this -> connection -> error);
            }
            if (count($this -> parameters) == 0) {
                return $statement;
            }
            $callParameters = [];
            $types = '';
            $blobs = [];
            $callParameters[] = &$types;
            $counter = 0;
            foreach ($this -> parameters as $i => $parameter) {
                $values = is_array($parameter[1]) ? $parameter[1] : [$parameter[1]];
                foreach ($values as $value) {
                    $types .= $parameter[0];
                    if ($parameter[0] == 'b') {
                        $nothing = null;
                        $callParameters[] = &$nothing;
                        $blobs[$counter] = $value;
                    } else {
                        $name = "value{$counter}";
                        $$name = $value;
                        $callParameters[] = &$$name;
                    }
                    $counter++;
                }
            }
            $success = call_user_func_array([$statement, 'bind_param'], $callParameters);
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            foreach ($blobs as $i => $data) {
                $success = $statement -> send_long_data($i, $data);
                if (!$success) {
                    throw new Exception($this -> connection -> error);
                }
            }
            return $statement;
        }

        private function initFetching() : array {
            $statement = $this -> bindParameters();
            $row = $this -> bindResult($statement);
            $success = $statement -> execute();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            $statement -> store_result();
            return [$row, $statement];
        }

        private function finalizeFetching(mysqli_stmt $statement) {
            $statement -> free_result();
            $success = $statement -> close();
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
        }

        private function bindResult(mysqli_stmt $statement) : array {
            $metaData = $statement -> result_metadata();
            if ($metaData === false) {
                throw new Exception($this -> connection -> error);
            }
            $row = [];
            $boundParameters = [];
            while ($field = $metaData -> fetch_field()) {
                $boundParameters[] = &$row[$field -> name];
            }
            $success = call_user_func_array([$statement, 'bind_result'], $boundParameters);
            if (!$success) {
                throw new Exception($this -> connection -> error);
            }
            return $row;
        }
    }
}