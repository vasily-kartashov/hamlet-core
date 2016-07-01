<?php

namespace Hamlet\Entity {

    abstract class AbstractJsonEntity extends AbstractEntity {

        abstract protected function getData();

        public function getContent() : string {
            return json_encode($this->getData());
        }

        public function getMediaType() : string {
            return 'application/json;charset=UTF-8';
        }
    }
}