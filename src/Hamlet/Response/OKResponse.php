<?php

namespace Hamlet\Response {

    use Hamlet\Entity\Entity;

    /**
     * The provider has succeeded. The information returned with the provider is dependent on the method used in the
     * provider, for example:
     *
     * GET    an entity corresponding to the requested resource is sent in the provider
     * HEAD   the entity-header fields corresponding to the requested resource are sent in the provider without any
     *        message-body
     * POST   an entity describing or containing the result of the action
     * TRACE  an entity containing the provider message as received by the end server
     */
    class OKResponse extends AbstractResponse {

        public function __construct(Entity $entity = null) {
            parent::__construct('200 OK');
            if (!is_null($entity)) {
                $this->setEntity($entity);
            }
        }
    }
}