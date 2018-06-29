<?php

namespace Hamlet\Database\MySQL;

use Exception;
use Hamlet\Database\AbstractProcedure;
use Hamlet\Database\Stream\Selector as StreamSelector;
use mysqli;
use mysqli_stmt;

class MySQLProcedure extends AbstractProcedure
{
    /** @var mysqli */
    protected $connection;

    /** @var string */
    protected $query;

    public function __construct(mysqli $connection, string $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * @return void
     * @throws MySQLException
     */
    public function execute()
    {
        $statement = $this->bindParameters();
        $executionSucceeded = $statement->execute();
        if ($executionSucceeded === false) {
            throw new MySQLException($this->connection);
        } else {
            $closeSucceeded = $statement->close();
            if (!$closeSucceeded) {
                throw new MySQLException($this->connection);
            }
        }
    }

    /**
     * @return int
     * @throws MySQLException
     */
    public function insert(): int
    {
        $this->execute();
        return $this->connection->insert_id;
    }

    /**
     * @param callable $callback
     * @return void
     * @throws MySQLException
     */
    public function fetch(callable $callback)
    {
        /** @var mysqli_stmt $statement */
        list($row, $statement) = $this->initFetching();
        while (true) {
            $status = $statement->fetch();
            if ($status === true) {
                $rowCopy = [];
                foreach ($row as $key => $value) {
                    $rowCopy[$key] = $value;
                }
                \call_user_func_array($callback, [$rowCopy]);
            } elseif ($status === null) {
                break;
            } else {
                throw new MySQLException($this->connection);
            }
        }
        $this->finalizeFetching($statement);
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function fetchOne()
    {
        /** @var mysqli_stmt $statement */
        list($row, $statement) = $this->initFetching();
        $status = $statement->fetch();
        $value = null;
        if ($status === true) {
            $value = $row;
        } elseif ($status === false) {
            throw new MySQLException($this->connection);
        }
        $this->finalizeFetching($statement);
        return $value;
    }

    /**
     * @return array
     * @throws MySQLException
     */
    public function fetchAll(): array
    {
        $result = [];
        $this->fetch(/** @return void */ function (array $row) use (&$result) {
            $result[] = $row;
        });
        return $result;
    }

    public function stream(): StreamSelector
    {
        return new StreamSelector(function () {
            /** @var mysqli_stmt $statement */
            list($row, $statement) = $this->initFetching();
            $index = 0;
            while (true) {
                $status = $statement->fetch();
                if ($status === true) {
                    $rowCopy = [];
                    foreach ($row as $key => $value) {
                        $rowCopy[$key] = $value;
                    }
                    yield [$index++, $rowCopy];
                } elseif ($status === null) {
                    break;
                } else {
                    throw new MySQLException($this->connection);
                }
            }
            $this->finalizeFetching($statement);
        });
    }


    public function affectedRows(): int
    {
        return $this->connection->affected_rows;
    }

    /**
     * @return mysqli_stmt
     * @throws MySQLException
     */
    private function bindParameters(): mysqli_stmt
    {
        $query = $this->query;

        if (!empty($this->parameters)) {
            $position = 0;
            $counter = 0;
            while (true) {
                $position = \strpos($query, '?', $position);
                if ($position === false) {
                    break;
                }
                $value = $this->parameters[$counter++][1];
                if (is_array($value)) {
                    $in = '(' . \join(', ', \array_fill(0, \count($value), '?')) . ')';
                    $query = \substr($query, 0, $position) . $in . \substr($query, $position + 1);
                    $position += \strlen($in);
                } else {
                    $position++;
                }
            }
        }
        $statement = $this->connection->prepare($query);
        if ($statement === false) {
            throw new MySQLException($this->connection);
        }
        if (count($this->parameters) == 0) {
            return $statement;
        }
        $callParameters = [];
        $types = '';
        $blobs = [];
        $callParameters[] = &$types;
        $counter = 0;
        foreach ($this->parameters as $i => $parameter) {
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
        $success = \call_user_func_array([$statement, 'bind_param'], $callParameters);
        if (!$success) {
            throw new MySQLException($this->connection);
        }
        foreach ($blobs as $i => $data) {
            $success = $statement->send_long_data($i, $data);
            if (!$success) {
                throw new MySQLException($this->connection);
            }
        }
        return $statement;
    }

    /**
     * @return array
     * @throws MySQLException
     */
    private function initFetching(): array
    {
        $statement = $this->bindParameters();
        $row = $this->bindResult($statement);
        $success = $statement->execute();
        if (!$success) {
            throw new MySQLException($this->connection);
        }
        $statement->store_result();
        return [$row, $statement];
    }

    /**
     * @param mysqli_stmt $statement
     * @return void
     * @throws MySQLException
     */
    private function finalizeFetching(mysqli_stmt $statement)
    {
        $statement->free_result();
        $success = $statement->close();
        if (!$success) {
            throw new MySQLException($this->connection);
        }
    }

    /**
     * @param mysqli_stmt $statement
     * @return array
     * @throws MySQLException
     */
    private function bindResult(mysqli_stmt $statement): array
    {
        $metaData = $statement->result_metadata();
        if ($metaData === false) {
            throw new MySQLException($this->connection);
        }
        $row = [];
        $boundParameters = [];
        while ($field = $metaData->fetch_field()) {
            $row[$field->name] = null;
            $boundParameters[] = &$row[$field->name];
        }
        $success = call_user_func_array([$statement, 'bind_result'], $boundParameters);
        if (!$success) {
            throw new MySQLException($this->connection);
        }
        return $row;
    }
}
