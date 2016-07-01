<?php

namespace Hamlet\Resource {

    use Hamlet\Entity\Entity;
    use Hamlet\Request\Request;
    use Hamlet\Response\MethodNotAllowedResponse;
    use Hamlet\Response\OKOrNotModifiedResponse;
    use Hamlet\Response\Response;

    class EntityResource implements Resource {

        protected $entity;
        protected $methods;

        public function __construct(Entity $entity, array $methods = ['GET']) {
            $this->entity = $entity;
            $this->methods = $methods;
        }

        public function getResponse(Request $request) : Response {
            if (in_array($request->getMethod(), $this->methods)) {
                $response = new OKOrNotModifiedResponse($this->entity, $request);
                return $response;
            }
            return new MethodNotAllowedResponse($this->methods);
        }
    }
}
