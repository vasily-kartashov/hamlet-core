<?php

namespace Hamlet\Resources;

use Hamlet\Entities\JsonEntity;

class JsonEntityResource extends EntityResource
{
    /**
     * JsonEntityResource constructor.
     * @param mixed $value
     * @param string[] $methods
     */
    public function __construct($value, array $methods = ['GET', 'POST'])
    {
        parent::__construct(new JsonEntity($value), $methods);
    }
}
