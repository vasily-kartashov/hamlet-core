<?php

namespace Hamlet\Entities;

class PlainTextEntity extends AbstractEntity
{
    /** @var string */
    private $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getKey(): string
    {
        return md5($this->data);
    }

    public function getMediaType()
    {
        return "text/plain";
    }

    public function getContent(): string
    {
        return $this->data;
    }
}
