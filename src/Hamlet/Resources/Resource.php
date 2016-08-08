<?php

namespace Hamlet\Resources {

    use Hamlet\Requests\Request;
    use Hamlet\Responses\Response;

    interface Resource {

        public function getResponse(Request $request) : Response;
    }
}