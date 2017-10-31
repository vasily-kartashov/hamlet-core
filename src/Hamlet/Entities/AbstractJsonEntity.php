<?php

namespace Hamlet\Entities;

abstract class AbstractJsonEntity extends AbstractEntity
{
    public function getContent(): string
    {
        $json = json_encode($this->getData());
        return $json !== false ? $json : '';
    }

    abstract protected function getData();

    public function getMediaType(): string
    {
        return 'application/json;charset=UTF-8';
    }
}
