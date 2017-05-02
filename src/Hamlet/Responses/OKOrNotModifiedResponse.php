<?php

namespace Hamlet\Responses;

use Hamlet\Cache\Cache;
use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;

class OKOrNotModifiedResponse extends Response
{

    public function __construct(Entity $entity, Request $request)
    {
        parent::__construct();
        $this->setEntity($entity);
    }

    public function output(Request $request, Cache $cache)
    {
        if ($request->preconditionFulfilled($this->entity, $cache)) {
            $this->setStatus(200);
            $this->setEmbedEntity(true);
        } else {
            $this->setStatus(304);
            $this->setEmbedEntity(false);
        }
        parent::output($request, $cache);
    }
}

