<?php

namespace Hamlet\Entities;

use RuntimeException;

class JsonEntity extends AbstractJsonEntity
{
    /** @var mixed */
    private $data;

    /** @var string|null */
    private $content = null;

    /** @var string|null */
    private $key = null;

    /**
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the entity key, still important for 304 to use proper key
     * @return string
     */
    public function getKey(): string
    {
        if ($this->key === null) {
            $this->key = \crc32($this->getContent());
        }
        return $this->key;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = \json_encode($this->data);
        }
        if ($this->content === false) {
            throw new RuntimeException(\json_last_error_msg(), \json_last_error());
        }
        return $this->content;
    }

    /**
     * Get entity data
     * @return mixed
     */
    protected function getData()
    {
        return $this->data;
    }
}
