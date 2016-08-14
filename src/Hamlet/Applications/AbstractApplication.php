<?php

namespace Hamlet\Applications {

    use Hamlet\Cache\Cache;
    use Hamlet\Requests\Request;
    use Hamlet\Resources\Resource;
    use Hamlet\Responses\Response;

    abstract class AbstractApplication {

        public function run(Request $request) : Response {
            /** @var \Hamlet\Resources\Resource $resource */
            $resource = $this -> findResource($request);
            $response = $resource -> getResponse($request);
            return $response;
        }

        abstract protected function findResource(Request $request) : Resource;

        abstract protected function getCache(Request $request) : Cache;

        public function output(Request $request, Response $response) : void {
            $response->output($request, $this -> getCache($request));
        }
    }
}
