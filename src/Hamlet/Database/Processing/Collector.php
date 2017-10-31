<?php

namespace Hamlet\Database\Processing;

class Collector
{
    /** @var array */
    protected $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public function collectAll(): array
    {
        return $this->records;
    }

    /**
     * @return mixed
     */
    public function collectHead()
    {
        if (empty($this->records)) {
            return null;
        }
        return \reset($this->records);
    }
}
