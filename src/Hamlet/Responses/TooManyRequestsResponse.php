<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;

/**
 * The user has sent too many requests in a given amount of time. Intended for use with rate-limiting schemes.
 */
class TooManyRequestsResponse extends Response
{
    public function __construct(Entity $entity = null)
    {
        parent::__construct(429);
        if ($entity) {
            $this->withEntity($entity);
        }
    }
}
