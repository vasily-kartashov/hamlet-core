<?php

namespace Hamlet\Entity {

    class JsonEntity extends AbstractJsonEntity {

        protected $data;

        public function __construct($data) {
            $this->data = $data;
        }

        /**
         * Get entity data
         */
        protected function getData() {
            return $this->data;
        }

        /**
         * Get the entity key, still important for 304 to use proper key
         */
        public function getKey() : string {
            return md5(json_encode($this->data));
        }
    }
}
