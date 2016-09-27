<?php

namespace Hamlet\Database {

    interface Procedure {

        public function bindBlob(string $value);

        public function bindFloat(float $value);

        public function bindInteger(int $value);

        public function bindString(string $value);

        public function execute();

        public function fetch(callable $callback);

        public function fetchAll() : array;

        public function fetchAllWithKey(string $keyField) : array;

        public function fetchOne();
    }
}
