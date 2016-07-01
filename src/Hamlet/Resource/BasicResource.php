<?php

namespace Hamlet\Resource {

    use Hamlet\Request\Request;
    use Hamlet\Response\Response;

    class BasicResource implements Resource {

        protected $response;

        public function __construct(Response $response) {
            $this->response = $response;
        }

        public function getResponse(Request $request) : Response {
            return $this->response;
        }
    }
}
