<?php

namespace Hamlet\Resource;

use Hamlet\Entity\EntityInterface;
use Hamlet\Request\RequestInterface;
use Hamlet\Response\MethodNotAllowedResponse;
use Hamlet\Response\OKOrNotModifiedResponse;

class EntityResource implements ResourceInterface
{
    protected $entity;
    protected $methods;

    /**
     * @param \Hamlet\Entity\EntityInterface $entity
     * @param string[] $methods
     */
    public function __construct(EntityInterface $entity, $methods = ['GET'])
    {
        $this->entity = $entity;
        $this->methods = $methods;
    }

    /**
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\MethodNotAllowedResponse|\Hamlet\Response\OKOrNotModifiedResponse
     */
    public function getResponse(RequestInterface $request)
    {
        if (in_array($request->getMethod(), $this->methods)) {
            $response = new OKOrNotModifiedResponse($this->entity, $request);
            return $response;
        }
        return new MethodNotAllowedResponse($this->methods);
    }
}

