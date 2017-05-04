<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;
use Psr\Cache\CacheItemPoolInterface;

class OKOrNotModifiedResponse extends Response
{

    public function __construct(Entity $entity, Request $request)
    {
        parent::__construct();
        $this->withEntity($entity);
    }

    public function output(Request $request, CacheItemPoolInterface $cache)
    {
        if ($request->preconditionFulfilled($this->entity, $cache)) {
            $this->withStatusCode(200)->withEmbedEntity(true);
        } else {
            $this->withStatusCode(304)->withEmbedEntity(false);
        }
        parent::output($request, $cache);
    }
}

