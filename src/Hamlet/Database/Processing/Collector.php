<?php

namespace Hamlet\Database\Processing;

class Collector
{
    protected $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public function collectAll(): array
    {
        return $this->records;
    }

    public function collectHead()
    {
        if (empty($this->records)) {
            return null;
        }
        return \reset($this->records);
    }
}
