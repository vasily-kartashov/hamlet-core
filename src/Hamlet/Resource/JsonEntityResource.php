<?php

namespace Hamlet\Resource {

    use Hamlet\Entity\JsonEntity;

    class JsonEntityResource extends EntityResource {
        
        public function __construct($value, array $methods = ['GET', 'POST']) {
            parent::__construct(new JsonEntity($value), $methods);
        }
    }
}