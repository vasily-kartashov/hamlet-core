<?php

namespace Hamlet\Resource;

use Hamlet\Request\RequestInterface;
use Hamlet\Response\AbstractResponse;

class Resource implements ResourceInterface
{
    /**
     * @var \Hamlet\Response\AbstractResponse
     */
    protected $response;

    /**
     * @param \Hamlet\Response\AbstractResponse $response
     */
    public function __construct(AbstractResponse $response)
    {
        $this->response= $response;
    }

    /**
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\MethodNotAllowedResponse|\Hamlet\Response\OKOrNotModifiedResponse
     */
    public function getResponse(RequestInterface $request)
    {
        return $this->response;
    }
}

