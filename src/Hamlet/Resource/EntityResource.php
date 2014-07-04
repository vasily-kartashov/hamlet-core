<?php

namespace Hamlet\Resource;

use Hamlet\Entity\EntityInterface;
use Hamlet\Request\RequestInterface;
use Hamlet\Response\MethodNotAllowedResponse;
use Hamlet\Response\OKORNotModifiedResponse;

class EntityResource implements ResourceInterface
{
    protected $entity;

    /**
     * @param \Hamlet\Entity\EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\MethodNotAllowedResponse|\Hamlet\Response\OKOrNotModifiedResponse
     */
    public function getResponse(RequestInterface $request)
    {
        if ($request->getMethod() == 'GET') {
            $response = new OKOrNotModifiedResponse($this->entity, $request);
            return $response;
        }
        return new MethodNotAllowedResponse(array('GET'));
    }
}

