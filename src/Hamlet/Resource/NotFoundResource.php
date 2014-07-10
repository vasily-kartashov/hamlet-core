<?php

namespace Hamlet\Resource;

use Hamlet\Entity\EntityInterface;
use Hamlet\Request\RequestInterface;
use Hamlet\Response\MethodNotAllowedResponse;
use Hamlet\Response\NotFoundResponse;

class NotFoundResource implements ResourceInterface
{
    protected $entity;

    /**
     * @param \Hamlet\Entity\EntityInterface $entity
     */
    public function __construct(EntityInterface $entity = null)
    {
        $this->entity = $entity;
    }

    /**
     * @param \Hamlet\Request\RequestInterface $request
     *
     * @return \Hamlet\Response\MethodNotAllowedResponse|\Hamlet\Response\NotFoundResponse
     */
    public function getResponse(RequestInterface $request)
    {
        if ($request->getMethod() == 'GET') {
            $response = new NotFoundResponse($this->entity);
            $response->setHeader('Cache-Control', 'private');
            return $response;
        }
        return new MethodNotAllowedResponse(['GET']);
    }
}
