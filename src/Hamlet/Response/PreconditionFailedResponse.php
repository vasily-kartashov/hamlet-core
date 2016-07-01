<?php

namespace Hamlet\Response {

    use Hamlet\Entity\Entity;

    class PreconditionFailedResponse extends AbstractResponse {
        
        public function __construct(Entity $entity = null) {
            parent::__construct('412 Precondition Failed');
            if (!is_null($entity)) {
                $this->setEntity($entity);
            }
        }
    }
}