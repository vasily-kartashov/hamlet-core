<?php

namespace Hamlet\Resource;

use Hamlet\Entity\JsonEntity;

class JsonEntityResource extends EntityResource
{
    /**
     * @param mixed $value
     * @param string[] $methods
     */
    public function __construct($value, $methods = ['GET'])
    {
        parent::__construct(new JsonEntity($value));
    }
}