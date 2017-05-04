<?php

namespace Hamlet\Database\Processing;

class Collector
{
    protected $records;

    public function __construct($records = [])
    {
        $this->records = $records;
    }

    public function collectToList(): array
    {
        return $this->records;
    }

    public function collectHead()
    {
        return $this->records[0] ?? null;
    }
}