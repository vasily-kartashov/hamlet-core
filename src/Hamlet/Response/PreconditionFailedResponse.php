<?php

namespace Hamlet\Response;

use Hamlet\Entity\EntityInterface;

class PreconditionFailedResponse extends AbstractResponse
{
    public function __construct(EntityInterface $entity = null)
    {
        parent::__construct('412 Precondition Failed');
        if (!is_null($entity)) {
            $this->setEntity($entity);
        }
    }
}
