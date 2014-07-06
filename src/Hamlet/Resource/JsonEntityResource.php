<?php

namespace Hamlet\Resource;

use Hamlet\Entity\JsonEntity;

class JsonEntityResource extends EntityResource
{
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        parent::__construct(new JsonEntity($value));
    }
}