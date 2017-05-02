<?php

namespace Hamlet\Entities;

abstract class AbstractJsonEntity extends AbstractEntity
{
    public function getContent(): string
    {
        return json_encode($this->getData());
    }

    abstract protected function getData();

    public function getMediaType(): string
    {
        return 'application/json;charset=UTF-8';
    }
}
