<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;

class PreconditionFailedResponse extends Response
{
    public function __construct(Entity $entity = null)
    {
        parent::__construct(412);
        if (!is_null($entity)) {
            $this->setEntity($entity);
        }
    }
}
