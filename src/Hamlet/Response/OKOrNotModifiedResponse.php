<?php

namespace Hamlet\Response {

    use Hamlet\Cache\Cache;
    use Hamlet\Entity\Entity;
    use Hamlet\Request\Request;

    class OKOrNotModifiedResponse extends AbstractResponse {

        public function __construct(Entity $entity, Request $request) {
            parent::__construct();
            $this->setEntity($entity);
        }

        public function output(Request $request, Cache $cache) : void {
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
}
