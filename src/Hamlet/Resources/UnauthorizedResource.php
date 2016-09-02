<?php

namespace Hamlet\Resources {

    use Hamlet\Requests\Request;
    use Hamlet\Responses\UnauthorizedResponse;

    class UnauthorizedResource implements Resource {

        public function getResponse(Request $request) : UnauthorizedResponse {
            return new UnauthorizedResponse();
        }
    }
}