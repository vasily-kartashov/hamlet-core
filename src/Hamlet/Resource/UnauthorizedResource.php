<?php

namespace Hamlet\Resource {

    use Hamlet\Request\Request;
    use Hamlet\Response\UnauthorizedResponse;

    class UnauthorizedResource implements Resource {

        public function getResponse(Request $request) : UnauthorizedResponse {
            return new UnauthorizedResponse();
        }
    }
}