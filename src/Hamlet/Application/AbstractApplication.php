<?php

namespace Hamlet\Application;

use Hamlet\Request\RequestInterface;
use Hamlet\Response\ResponseInterface;

abstract class AbstractApplication
{
    /** @var \Memcached */
    private $cache = null;

    /**
     * Find requested resource
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Resource\ResourceInterface
     */
    abstract protected function findResource(RequestInterface $request);

    /**
     * Find response for the specified request
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\ResponseInterface
     */
    public function run(RequestInterface $request)
    {
        $resource = $this->findResource($request);
        $response = $resource->getResponse($request);
        return $response;
    }

    /**
     * Return cache server location, for example
     * array('localhost', 11211)
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Cache\CacheInterface
     */
    abstract protected function getCache(RequestInterface $request);

    /**
     * Output the response to the standard output stream
     * @param \Hamlet\Request\RequestInterface $request
     * @param \Hamlet\Response\ResponseInterface $response
     * @return void
     */
    public function output(RequestInterface $request, ResponseInterface $response)
    {
        $response->output($request, $this->getCache($request));
    }
}