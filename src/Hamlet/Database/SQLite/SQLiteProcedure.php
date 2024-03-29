<?php

namespace Hamlet\Database\SQLite;

use Hamlet\Database\AbstractProcedure;
use Hamlet\Database\Stream\Selector as StreamSelector;
use SQLite3;
use SQLite3Stmt;
use function array_fill;
use function count;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;
use function is_array;
use function join;
use function strlen;
use function strpos;
use function substr;

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

    public function execute(): void
    {
        $this->bindParameters()->execute();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function fetchOne(): ?array
    {
        $result = $this->bindParameters()->execute();
        $record = $result->fetchArray(SQLITE3_ASSOC);
        if ($record !== false) {
            return _map(_string(), _mixed())->cast($record);
        }
        return null;
    }

    /**
     * @return array<array<string,mixed>>
     */
    public function fetchAll(): array
    {
        $result = $this->bindParameters()->execute();
        $data = [];
        $type = _map(_string(), _mixed());
        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $data[] = $type->cast($row);
        }
        return $data;
    }

    public function stream(): StreamSelector
    {
        return new StreamSelector(function () {
            $result = $this->bindParameters()->execute();
            $index = 0;
            while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
                yield [$index++, $row];
            }
        });
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
            throw new SQLiteException('Cannot prepare statement ' . $query);
        }
        $counter = 1;
        foreach ($this->parameters as list($typeAlias, $value)) {
            $type = $this->resolveTypeAlias($typeAlias);
            if ($value === null) {
                $statement->bindValue($counter++, null, SQLITE3_NULL);
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    $statement->bindValue($counter++, $item, $type);
                }
            } else {
                $statement->bindValue($counter++, $value, $type);
            }
        }
        $this->parameters = [];
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
                throw new SQLiteException('Cannot resolve type alias "' . $alias . '"');
        }
    }
}
