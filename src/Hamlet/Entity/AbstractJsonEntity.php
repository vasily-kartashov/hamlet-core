<?php
namespace Hamlet\Entity;

abstract class AbstractJsonEntity extends AbstractEntity
{
    /**
     * Get entity data
     * @return mixed
     */
    abstract protected function getData();

    /**
     * Get entity content
     * @return string
     */
    public function getContent()
    {
        return json_encode($this->getData());
    }

    /**
     * Get entity media type
     * @return string
     */
    public function getMediaType()
    {
        return 'application/json;charset=UTF-8';
    }
}
