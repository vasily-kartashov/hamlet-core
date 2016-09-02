<?php

namespace Hamlet\Resources {

    use Hamlet\Requests\Request;
    use Hamlet\Responses\Response;

    interface WebResource {

        public function getResponse(Request $request) : Response;
    }
}