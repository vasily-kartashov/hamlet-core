<?php

namespace Hamlet\Entities;

use Exception;

class JsonEntity extends AbstractJsonEntity
{
    /** @var mixed */
    protected $data;

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
     * @throws Exception
     */
    public function getKey(): string
    {
        $json = \json_encode($this->data);
        if ($json === false) {
            throw new Exception('Cannot serialize data');
        }
        return md5($json);
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
