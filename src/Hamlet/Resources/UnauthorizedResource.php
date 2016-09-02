<?php

namespace Hamlet\Resources {

    use Hamlet\Requests\Request;
    use Hamlet\Responses\{Response, UnauthorizedResponse};

    class UnauthorizedResource implements WebResource {

        public function getResponse(Request $request) : Response {
            return new UnauthorizedResponse();
        }
    }
}