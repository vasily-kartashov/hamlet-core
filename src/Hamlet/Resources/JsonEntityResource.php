<?php

namespace Hamlet\Resources;

use Hamlet\Entities\JsonEntity;

class JsonEntityResource extends EntityResource
{
    /**
     * @param mixed $value
     * @param string ...$methods
     */
    public function __construct($value, string ...$methods)
    {
        parent::__construct(new JsonEntity($value), ...$methods);
    }
}
