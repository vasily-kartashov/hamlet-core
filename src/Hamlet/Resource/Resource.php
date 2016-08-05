<?php

namespace Hamlet\Resource {

    use Hamlet\Request\Request;
    use Hamlet\Response\Response;

    interface Resource {

        public function getResponse(Request $request) : Response;
    }
}