<?php

namespace Hamlet\Database\MySQL;

use Exception;
use Hamlet\Database\AbstractProcedure;
use Hamlet\Database\Stream\Selector as StreamSelector;
use mysqli;
use mysqli_stmt;
use function array_fill;
use function call_user_func_array;
use function count;
use function Hamlet\Cast\_string;
use function join;
use function strlen;
use function strpos;
use function substr;

class MySQLProcedure extends AbstractProcedure
{
    /**
     * @var mysqli
     */
    protected $connection;

    /** @var string */
    protected $query;

    public function __construct(mysqli $connection, string $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * @throws MySQLException
     */
    public function execute(): void
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
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
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
        list($row, $statement) = $this->initFetching();
        while (true) {
            $status = $statement->fetch();
            if ($status === true) {
                $rowCopy = [];
                foreach ($row as $key => $value) {
                    $rowCopy[$key] = $value;
                }
                call_user_func_array($callback, [$rowCopy]);
            } elseif ($status === null) {
                break;
            } else {
                throw new MySQLException($this->connection);
            }
        }
        $this->finalizeFetching($statement);
    }

    /**
     * @return array<string,mixed>|null
     * @throws Exception
     */
    public function fetchOne(): ?array
    {
        [$row, $statement] = $this->initFetching();
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
     * @return array<array<string,mixed>>
     * @throws MySQLException
     */
    public function fetchAll(): array
    {
        $result = [];
        [$row, $statement] = $this->initFetching();
        while (true) {
            $status = $statement->fetch();
            if ($status === true) {
                $rowCopy = [];
                foreach ($row as $key => $value) {
                    $rowCopy[$key] = $value;
                }
                $result[] = $rowCopy;
            } elseif ($status === null) {
                break;
            } else {
                throw new MySQLException($this->connection);
            }
        }
        $this->finalizeFetching($statement);
        return $result;
    }

    public function stream(): StreamSelector
    {
        return new StreamSelector(function () {
            [$row, $statement] = $this->initFetching();
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
        $statement = $this->connection->prepare($query);
        if ($statement === false) {
            throw new MySQLException($this->connection);
        }
        if (count($this->parameters) == 0) {
            return $statement;
        }
        $callParameters = [];
        $types = '';
        foreach ($this->parameters as $parameter) {
            $values = is_array($parameter[1]) ? $parameter[1] : [$parameter[1]];
            foreach ($values as $value) {
                $types .= $parameter[0];
                $callParameters[] = $value;
            }
        }
        $success = $statement->bind_param($types, ...$callParameters);
        if (!$success) {
            throw new MySQLException($this->connection);
        }
        $this->parameters = [];
        return $statement;
    }

    /**
     * @return array{array<string,mixed>,mysqli_stmt}
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
     * @return array<string,mixed>
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
            $fieldName = _string()->cast($field->name);
            $row[$fieldName] = null;
            $boundParameters[] = &$row[$fieldName];
        }
        $success = call_user_func_array([$statement, 'bind_result'], $boundParameters);
        if (!$success) {
            throw new MySQLException($this->connection);
        }
        return $row;
    }
}
