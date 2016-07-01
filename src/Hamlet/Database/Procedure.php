<?php

namespace Hamlet\Database {

    interface Procedure {

        public function bindBlob(string $value);

        public function bindFloat(float $value);

        public function bindInteger(int $value);

        public function bindString(string $value);

        public function execute() : void;

        public function fetch(callable $callback);

        /**
         * @return array[]
         */
        public function fetchAll() : array;

        /**
         * @param string $keyField
         * @return array[]
         */
        public function fetchAllWithKey(string $keyField) : array;

        public function fetchOne() : array;
    }
}
