<?php

namespace Hamlet\Database\SQLite;

use Hamlet\Database\DatabaseException;

class SQLiteException extends DatabaseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
