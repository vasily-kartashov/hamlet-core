<?php

namespace Hamlet\Database\PDO;

use Exception;
use Hamlet\Database\AbstractProcedure;
use PDO;
use PDOStatement;

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
     * @return string
     */
    public function insert(): string
    {
        $statement = $this->prepareAndBind();
        $this->affectedRows = $statement->rowCount();
        return $this->connection->lastInsertId();
    }

    /**
     * @return void
     */
    public function execute()
    {
        $statement = $this->prepareAndBind();
        $statement->execute();
        $this->affectedRows = $statement->rowCount();
    }

    /**
     * @return array|null
     */
    public function fetchOne()
    {
        $statement = $this->prepareAndBind();
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll(): array
    {
        $statement = $this->prepareAndBind();
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function affectedRows(): int
    {
        return $this->affectedRows;
    }

    private function prepareAndBind(): PDOStatement
    {
        $query = $this->query;
        $position = 0;
        $counter = 0;
        if (!empty($this->parameters)) {
            while (true) {
                $position = \strpos($query, '?', $position);
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
            if (is_null($value)) {
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
                throw new Exception('Cannot resolve type alias "' . $alias . '"');
        }
    }
}
