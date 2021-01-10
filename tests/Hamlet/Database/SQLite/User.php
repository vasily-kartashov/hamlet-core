<?php

namespace Hamlet\Database\SQLite;

use Hamlet\Database\Entity;

class User implements Entity
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string[] */
    private $addresses;

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function addresses(): array
    {
        return $this->addresses;
    }
}
