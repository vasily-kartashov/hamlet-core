<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;
use Hamlet\Writers\ResponseWriter;
use Psr\Cache\CacheItemPoolInterface;

class OKOrNotModifiedResponse extends Response
{

    public function __construct(Entity $entity)
    {
        parent::__construct();
        $this->withEntity($entity);
    }

    public function output(Request $request, CacheItemPoolInterface $cache, ResponseWriter $writer): void
    {
        if ($this->entity && $request->preconditionFulfilled($this->entity, $cache)) {
            $this->withStatusCode(200)->withEmbedEntity(true);
        } else {
            $this->withStatusCode(304)->withEmbedEntity(false);
        }
        parent::output($request, $cache, $writer);
    }
}
