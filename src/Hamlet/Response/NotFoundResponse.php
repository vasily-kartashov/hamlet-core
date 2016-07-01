<?php

namespace Hamlet\Response {

    use Hamlet\Entity\Entity;

    /**
     * The server has not found anything matching the Request-URI. No indication is given of whether the condition is
     * temporary or permanent. The 410 (Gone) status code SHOULD be used if the server knows, through some internally
     * configurable mechanism, that an old resource is permanently unavailable and has no forwarding address. This
     * status code is commonly used when the server does not wish to reveal exactly why the provider has been refused, or
     * when no other provider is applicable.
     */
    class NotFoundResponse extends AbstractResponse {

        public function __construct(Entity $entity = null) {
            parent::__construct('404 Not Found');
            if (!is_null($entity)) {
                $this->setEntity($entity);
            }
        }
    }
}
