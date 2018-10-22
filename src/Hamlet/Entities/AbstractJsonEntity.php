<?php

namespace Hamlet\Entities;

abstract class AbstractJsonEntity extends AbstractEntity
{
    abstract protected function getData();

    public function getMediaType(): string
    {
        return 'application/json;charset=UTF-8';
    }
}
