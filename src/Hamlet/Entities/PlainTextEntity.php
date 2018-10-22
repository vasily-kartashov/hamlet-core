<?php

namespace Hamlet\Entities;

class PlainTextEntity extends AbstractEntity
{
    /** @var string */
    private $data;

    /** @var string|null */
    private $key = null;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getKey(): string
    {
        if ($this->key === null) {
            $this->key = \crc32($this->data);
        }
        return $this->key;
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
