<?php

namespace Hamlet\Response;

use Hamlet\Cache\CacheInterface;
use Hamlet\Request\RequestInterface;

interface ResponseInterface
{
    /**
     * @param \Hamlet\Request\RequestInterface $request
     * @param \Hamlet\Cache\CacheInterface $cache
     * @return void
     */
    public function output(RequestInterface $request, CacheInterface $cache);
}