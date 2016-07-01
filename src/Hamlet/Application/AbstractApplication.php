<?php

namespace Hamlet\Application {

    use Hamlet\Cache\Cache;
    use Hamlet\Request\Request;
    use Hamlet\Resource\Resource;
    use Hamlet\Response\Response;

    abstract class AbstractApplication {

        abstract protected function findResource(Request $request) : Resource;

        public function run(Request $request) : Response {
            $resource = $this->findResource($request);
            $response = $resource->getResponse($request);
            return $response;
        }

        abstract protected function getCache(Request $request) : Cache;

        public function output(Request $request, Response $response) : void {
            $response->output($request, $this->getCache($request));
        }
    }
}