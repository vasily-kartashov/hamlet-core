<?php

namespace Hamlet\Responses;

class Cookie
{
    /** @var string */
    private $name;

    /** @var string */
    private $value;

    /** @var string */
    private $path;

    /** @var int */
    private $timeToLive;

    public function __construct(string $name, string $value, string $path, int $timeToLive)
    {
        $this->name = $name;
        $this->value = $value;
        $this->path = $path;
        $this->timeToLive = $timeToLive;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function timeToLive(): int
    {
        return $this->timeToLive;
    }
}
