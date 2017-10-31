<?php

namespace Hamlet\Database\SQLite;

use Exception;
use Hamlet\Database\AbstractProcedure;
use SQLite3;
use SQLite3Stmt;

class SQLiteProcedure extends AbstractProcedure
{
    /** @var SQLite3 */
    private $connection;

    /** @var string */
    private $query;

    public function __construct(SQLite3 $connection, string $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * @return int
     */
    public function insert(): int
    {
        $this->execute();
        return $this->connection->lastInsertRowID();
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->bindParameters()->execute();
    }

    public function fetchOne(): array
    {
        $result = $this->bindParameters()->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
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

    public function affectedRows(): int
    {
        return $this->connection->changes();
    }

    private function bindParameters(): SQLite3Stmt
    {
        $query = $this->query;
        $position = 0;
        $counter = 0;
        if (!empty($this->parameters)) {
            while (true) {
                /** @psalm-suppress PossiblyFalseArgument */
                $position = \strpos($query, '?', $position);
                if ($position === false) {
                    break;
                }
                $value = $this->parameters[$counter++][1];
                if (\is_array($value)) {
                    $in = '(' . \join(', ', \array_fill(0, \count($value), '?')) . ')';
                    $query = \substr($query, 0, $position) . $in . \substr($query, $position + 1);
                    $position += \strlen($in);
                } else {
                    $position++;
                }
            }
        }
        $statement = $this->connection->prepare($query);
        $counter = 1;
        foreach ($this->parameters as list($typeAlias, $value)) {
            $type = $this->resolveTypeAlias($typeAlias);
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

    private function resolveTypeAlias(string $alias): int
    {
        switch ($alias) {
            case 'b':
                return SQLITE3_BLOB;
            case 'd':
                return SQLITE3_FLOAT;
            case 'i':
                return SQLITE3_INTEGER;
            case 's':
                return SQLITE3_TEXT;
            default:
                throw new Exception('Cannot resolve type alias "' . $alias . '"');
        }
    }
}
