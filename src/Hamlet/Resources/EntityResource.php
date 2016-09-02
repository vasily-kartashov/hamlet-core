<?php

namespace Hamlet\Resources {

    use Hamlet\Entities\Entity;
    use Hamlet\Requests\Request;
    use Hamlet\Responses\{MethodNotAllowedResponse, OKOrNotModifiedResponse, Response};

    class EntityResource implements WebResource {

        protected $entity;
        /** @var string[] */
        protected $methods;

        public function __construct(Entity $entity, array $methods = ['GET']) {
            $this -> entity = $entity;
            $this -> methods = $methods;
        }

        public function getResponse(Request $request) : Response {
            if (in_array($request -> getMethod(), $this -> methods)) {
                $response = new OKOrNotModifiedResponse($this -> entity, $request);
                return $response;
            }
            return new MethodNotAllowedResponse($this -> methods);
        }
    }
}
