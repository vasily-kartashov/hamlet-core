<?php

namespace Hamlet\Database\SQLite;

use RuntimeException;

class SQLiteException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
