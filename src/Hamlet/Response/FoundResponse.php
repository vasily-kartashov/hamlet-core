<?php

namespace Hamlet\Response {

    class FoundResponse extends AbstractResponse {
        
        public function __construct(string $url) {
            assert(is_string($url));
            parent::__construct('302 Found');
            $this->setHeader('Location', $url);
        }
    }
}