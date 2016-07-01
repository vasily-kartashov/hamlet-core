<?php

namespace Hamlet\Response {

    use Hamlet\Cache\Cache;
    use Hamlet\Request\Request;

    interface Response {
        
        public function output(Request $request, Cache $cache) : void;
    }
}