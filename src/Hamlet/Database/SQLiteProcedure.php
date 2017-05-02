<?php

namespace Hamlet\Database;

use \SQLite3;
use SQLite3Stmt;

class SQLiteProcedure implements Procedure
{

    private $connection;
    private $query;
    private $parameters = [];

    public function __construct(SQLite3 $connection, string $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    public function bindBlob(string $value)
    {
        $this->parameters[] = [SQLITE3_BLOB, $value];
    }

    public function bindFloat(float $value)
    {
        $this->parameters[] = [SQLITE3_FLOAT, $value];
    }

    public function bindInteger(int $value)
    {
        $this->parameters[] = [SQLITE3_INTEGER, $value];
    }

    public function bindString(string $value)
    {
        $this->parameters[] = [SQLITE3_TEXT, $value];
    }

    public function bindNullableBlob($value)
    {
        assert(is_null($value) || is_string($value));
        $this->parameters[] = [SQLITE3_BLOB, $value];
    }

    public function bindNullableFloat($value)
    {
        assert(is_null($value) || is_float($value));
        $this->parameters[] = [SQLITE3_FLOAT, $value];
    }

    public function bindNullableInteger($value)
    {
        assert(is_null($value) || is_int($value));
        $this->parameters[] = [SQLITE3_INTEGER, $value];
    }

    public function bindNullableString($value)
    {
        assert(is_null($value) || is_string($value));
        $this->parameters[] = [SQLITE3_TEXT, $value];
    }

    public function bindFloatList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_float($value));
        }
        $this->parameters[] = [SQLITE3_FLOAT, $values];
    }

    public function bindIntegerList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_int($value));
        }
        $this->parameters[] = [SQLITE3_INTEGER, $values];
    }

    public function bindStringList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_string($value));
        }
        $this->parameters[] = [SQLITE3_TEXT, $values];
    }

    public function insert()
    {
        $this->execute();
        return $this->connection->lastInsertRowID();
    }

    public function execute()
    {
        $this->bindParameters()->execute();
    }

    private function bindParameters(): SQLite3Stmt
    {
        $query = $this->query;
        $position = 0;
        $counter = 0;
        if (!empty($this->parameters)) {
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
        $counter = 1;
        foreach ($this->parameters as list($type, $value)) {
            if (is_null($value)) {
                $statement->bindValue($counter++, null, SQLITE3_NULL);
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    $statement->bindValue($counter++, $item, $type);
                }
            } else {
                $statement->bindValue($counter++, $value, $type);
            }
        }
        return $statement;
    }

    public function fetch(callable $callback)
    {
        $result = $this->bindParameters()->execute();
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            call_user_func_array($callback, [$row]);
        }
    }

    public function fetchAll(): array
    {
        $result = $this->bindParameters()->execute();
        $data = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $data[] = $row;
        }
        return $data;
    }

    public function fetchAllWithKey(string $keyField): array
    {
        $result = $this->bindParameters()->execute();
        $data = [];
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $key = $row[$keyField];
            $data[$key] = $row;
        }
        return $data;
    }

    public function fetchOne()
    {
        $result = $this->bindParameters()->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function affectedRows(): int
    {
        return $this->connection->changes();
    }
}
