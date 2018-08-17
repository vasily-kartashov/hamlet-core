<?php

namespace Hamlet\Applications;

use Hamlet\Requests\Request;
use Hamlet\Resources\WebResource;
use Hamlet\Responses\Response;
use Hamlet\Writers\ResponseWriter;
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
     * @param ResponseWriter $writer
     * @return void
     */
    public function output(Request $request, Response $response, ResponseWriter $writer)
    {
        $response->output($request, $this->getCache($request), $writer);
    }
}
