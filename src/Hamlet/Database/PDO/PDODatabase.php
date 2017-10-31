<?php

namespace Hamlet\Database\PDO;

use Hamlet\Database\Database;
use Hamlet\Database\Procedure;
use PDO;

class PDODatabase extends Database
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    public function prepare(string $query): Procedure
    {
        $procedure = new PDOProcedure($this->connection, $query);
        $procedure->setLogger($this->logger);
        return $procedure;
    }

    /**
     * @return void
     */
    public function startTransaction()
    {
        $this->logger->debug('Starting transaction');
        $this->connection->beginTransaction();
    }

    /**
     * @return void
     */
    public function commit()
    {
        $this->logger->debug('Committing transaction');
        $this->connection->commit();
    }

    /**
     * @return void
     */
    public function rollback()
    {
        $this->logger->debug('Rolling back transaction');
        $this->connection->rollBack();
    }
}
