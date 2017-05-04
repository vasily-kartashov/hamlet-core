<?php

namespace Hamlet\Responses;

class Cookie
{
    private $name;
    private $value;
    private $path;
    private $timeToLive;

    public function __construct(string $name, string $value, string $path, int $timeToLive)
    {
        $this->name = $name;
        $this->value = $value;
        $this->path = $path;
        $this->timeToLive = $timeToLive;
    }

    public function name()
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }

    public function path()
    {
        return $this->path;
    }

    public function timeToLive()
    {
        return $this->timeToLive;
    }
}
