<?php

namespace Hamlet\Entities;

abstract class AbstractJsonEntity extends AbstractEntity
{
    /**
     * @return mixed
     */
    abstract protected function getData();

    public function getMediaType(): string
    {
        return 'application/json;charset=UTF-8';
    }
}
