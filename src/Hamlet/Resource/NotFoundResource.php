<?php

namespace Hamlet\Resource {

    use Hamlet\Entity\Entity;
    use Hamlet\Request\Request;
    use Hamlet\Response\{MethodNotAllowedResponse, NotFoundResponse, Response};

    class NotFoundResource implements Resource {

        protected $entity;

        public function __construct(Entity $entity = null) {
            $this->entity = $entity;
        }

        public function getResponse(Request $request) : Response {
            if ($request->getMethod() == 'GET') {
                $response = new NotFoundResponse($this->entity);
                $response->setHeader('Cache-Control', 'private');
                return $response;
            }
            return new MethodNotAllowedResponse(['GET']);
        }
    }
}
