<?php

namespace Hamlet\Entities {

    class EntityLocationTuple {

        protected $location;
        protected $entity;

        public function __construct(string $location, Entity $entity) {
            $this -> location = $location;
            $this -> entity = $entity;
        }

        public function getLocation() : string {
            return $this -> location;
        }

        public function getEntity() : Entity {
            return $this -> entity;
        }
    }
}