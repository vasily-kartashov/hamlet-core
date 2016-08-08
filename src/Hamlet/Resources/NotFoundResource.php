<?php

namespace Hamlet\Resources {

    use Hamlet\Entities\Entity;
    use Hamlet\Requests\Request;
    use Hamlet\Responses\{MethodNotAllowedResponse, NotFoundResponse, Response};

    class NotFoundResource implements Resource {

        protected $entity;

        public function __construct(Entity $entity = null) {
            $this -> entity = $entity;
        }

        public function getResponse(Request $request) : Response {
            if ($request -> getMethod() == 'GET') {
                $response = new NotFoundResponse($this -> entity);
                $response -> setHeader('Cache-Control', 'private');
                return $response;
            }
            return new MethodNotAllowedResponse(['GET']);
        }
    }
}
