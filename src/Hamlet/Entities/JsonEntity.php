<?php

namespace Hamlet\Entities;

use RuntimeException;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function md5;

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
            $this->key = md5($this->getContent());
        }
        return $this->key;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        if ($this->content === null) {
            $content = json_encode($this->data);
            if ($content === false) {
                throw new RuntimeException(json_last_error_msg(), json_last_error());
            }
            $this->content = $content;
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
