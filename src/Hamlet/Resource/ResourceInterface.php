<?php

namespace Hamlet\Resource;

use Hamlet\Request\RequestInterface;

interface ResourceInterface
{
    /**
     * Get response object
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\ResponseInterface
     */
    public function getResponse(RequestInterface $request);
}