<?php

namespace Hamlet\Response;

use Hamlet\Entity\EntityInterface;

/**
 * Indicates that the resource has not been modified since the version specified by the request headers
 * If-Modified-Since or If-Match. This means that there is no need to retransmit the resource, since the client
 * still has a previously-downloaded copy.
 */
class NotModifiedResponse extends AbstractResponse
{
    /**
     * @param \Hamlet\Entity\EntityInterface $entity
     */
    public function __construct(EntityInterface $entity)
    {
        parent::__construct('304 Not Modified');
        $this->setEntity($entity, false);
    }
}
