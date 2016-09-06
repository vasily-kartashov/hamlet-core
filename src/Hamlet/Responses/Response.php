<?php

namespace Hamlet\Responses {

    use Hamlet\Cache\Cache;
    use Hamlet\Requests\Request;

    interface Response {

        public function output(Request $request, Cache $cache);
    }
}