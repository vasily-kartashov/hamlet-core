<?php

namespace Hamlet\Responses {

    /**
     * The method specified in the Requests-Line is not allowed for the resource identified by the Requests-URI. The
     * provider MUST include an Allow header containing a list of valid methods for the requested resource.
     */
    class MethodNotAllowedResponse extends AbstractResponse {

        public function __construct(array $allowedMethods) {
            parent::__construct('405 Method Not Allowed');
            $this -> setHeader('Allow', join(', ', $allowedMethods));
        }
    }
}