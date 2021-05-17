<?php

namespace Hamlet\Applications;

use Hamlet\Requests\Request;
use Hamlet\Resources\WebResource;
use Hamlet\Responses\Response;
use Hamlet\Writers\ResponseWriter;
use Psr\Cache\CacheItemPoolInterface;
use SessionHandlerInterface;

abstract class AbstractApplication
{
    public function run(Request $request): Response
    {
        $resource = $this->findResource($request);
        return $resource->getResponse($request);
    }

    abstract protected function findResource(Request $request): WebResource;

    abstract protected function getCache(Request $request): CacheItemPoolInterface;

    public function output(Request $request, Response $response, ResponseWriter $writer): void
    {
        $response->output($request, $this->getCache($request), $writer);
    }

    public function sessionHandler(): ?SessionHandlerInterface
    {
        return null;
    }
}
