<?php

namespace Hamlet\Responses {

    class FoundResponse extends AbstractResponse {

        public function __construct(string $url) {
            parent::__construct('302 Found');
            $this -> setHeader('Location', $url);
        }
    }
}