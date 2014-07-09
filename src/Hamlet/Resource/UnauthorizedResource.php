<?php

namespace Hamlet\Resource;

use Hamlet\Request\RequestInterface;
use Hamlet\Response\UnauthorizedResponse;

class UnauthorizedResource implements ResourceInterface
{

    /**
     * Get response object
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\ResponseInterface
     */
    public function getResponse(RequestInterface $request)
    {
        return new UnauthorizedResponse();
    }
}
