<?php

namespace Hamlet\Applications;

use Hamlet\Cache\Cache;
use Hamlet\Requests\Request;
use Hamlet\Resources\WebResource;
use Hamlet\Responses\Response;

abstract class AbstractApplication
{
    public function run(Request $request): Response
    {
        $resource = $this->findResource($request);
        $response = $resource->getResponse($request);
        return $response;
    }

    abstract protected function findResource(Request $request): WebResource;

    abstract protected function getCache(Request $request): Cache;

    public function output(Request $request, Response $response)
    {
        $response->output($request, $this->getCache($request));
    }
}
