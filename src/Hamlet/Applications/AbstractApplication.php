<?php

namespace Hamlet\Applications;

use Hamlet\Requests\Request;
use Hamlet\Resources\WebResource;
use Hamlet\Responses\Response;
use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractApplication
{
    public function run(Request $request): Response
    {
        $resource = $this->findResource($request);
        $response = $resource->getResponse($request);
        return $response;
    }

    abstract protected function findResource(Request $request): WebResource;

    abstract protected function getCache(Request $request): CacheItemPoolInterface;

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function output(Request $request, Response $response)
    {
        $response->output($request, $this->getCache($request));
    }
}
