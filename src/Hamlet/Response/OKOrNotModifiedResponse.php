<?php

namespace Hamlet\Response;

use Hamlet\Cache\CacheInterface;
use Hamlet\Entity\EntityInterface;
use Hamlet\Request\RequestInterface;

class OKOrNotModifiedResponse extends AbstractResponse
{
    /**
     * @param \Hamlet\Entity\EntityInterface $entity
     * @param \Hamlet\Request\RequestInterface $request
     */
    public function __construct(EntityInterface $entity, RequestInterface $request)
    {
        $this->setEntity($entity);
    }

    /**
     * @param \Hamlet\Request\RequestInterface $request
     * @param \Hamlet\Cache\CacheInterface $cache
     */
    public function output(RequestInterface $request, CacheInterface $cache)
    {
        if ($request->preconditionFulfilled($this->entity, $cache)) {
            $this->setStatus('200 OK');
            $this->setEmbedEntity(true);
        } else {
            $this->setStatus('304 Not Modified');
            $this->setEmbedEntity(false);
        }
        parent::output($request, $cache);
    }
}
