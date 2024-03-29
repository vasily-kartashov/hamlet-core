<?php

namespace Hamlet\Entities;

use function md5;

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
            $this->key = md5($this->data);
        }
        return $this->key;
    }

    public function getMediaType(): string
    {
        return "text/plain";
    }

    public function getContent(): string
    {
        return $this->data;
    }
}
