<?php
namespace Hamlet\Entity;

class JsonEntity extends AbstractJsonEntity
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get entity data
     * @return mixed
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Get the entity key, still important for 304 to use proper key
     * @return string
     */
    public function getKey()
    {
        return md5(json_encode($this->data));
    }
}

