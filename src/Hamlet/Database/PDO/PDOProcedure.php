<?php

namespace Hamlet\Database\PDO;

use Hamlet\Database\AbstractProcedure;
use Hamlet\Database\Stream\Selector as StreamSelector;
use PDO;
use PDOStatement;
use RuntimeException;
use function Hamlet\Cast\_list;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;
use function strpos;

class PDOProcedure extends AbstractProcedure
{
    /** @var PDO */
    private $connection;

    /** @var string */
    private $query;

    /** @var int */
    private $affectedRows = 0;

    public function __construct(PDO $connection, string $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * @return int
     */
    public function insert(): int
    {
        $statement = $this->prepareAndBind();
        $this->affectedRows = $statement->rowCount();
        return (int) $this->connection->lastInsertId();
    }

    public function execute(): void
    {
        $statement = $this->prepareAndBind();
        $statement->execute();
        $this->affectedRows = $statement->rowCount();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function fetchOne(): ?array
    {
        $statement = $this->prepareAndBind();
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if ($result === false) {
            return null;
        }
        return _map(_string(), _mixed())->cast($result);
    }

    /**
     * @return array<array<string,mixed>>
     */
    public function fetchAll(): array
    {
        $statement = $this->prepareAndBind();
        $statement->execute();
        $records = $statement->fetchAll(PDO::FETCH_ASSOC);
        return _list(_map(_string(), _mixed()))->cast($records);
    }

    public function stream(): StreamSelector
    {
        return new StreamSelector(function () {
            $statement = $this->prepareAndBind();
            $statement->execute();
            $index = 0;
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                yield [$index++, $row];
            }
        });
    }


    public function affectedRows(): int
    {
        return $this->affectedRows;
    }

    /**
     * @return PDOStatement
     */
    private function prepareAndBind(): PDOStatement
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
        foreach ($this->parameters as list($typeAlias, $value)) {
            $type = $this->resolveTypeAlias($typeAlias);
            if ($value === null) {
                $statement->bindValue($counter++, null, PDO::PARAM_NULL);
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
                return PDO::PARAM_LOB;
            case 'f':
            case 's':
                return PDO::PARAM_STR;
            case 'i':
                return PDO::PARAM_INT;
            default:
                throw new RuntimeException('Cannot resolve type alias "' . $alias . '"');
        }
    }
}
