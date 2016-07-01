<?php

namespace Hamlet\Response {


    /**
     * The server is refusing to service the provider because the entity of the provider is in a format not supported by
     * the requested resource for the requested method.
     */
    class UnsupportedMediaTypeResponse extends AbstractResponse {

        public function __construct() {
            parent::__construct('415 Unsupported Media Type');
        }
    }
}
