<?php

namespace Hamlet\Resources {

    use Hamlet\Requests\Request;
    use Hamlet\Responses\{MethodNotAllowedResponse, Response, TemporaryRedirectResponse};

    class RedirectResource implements Resource {

        protected $url;

        public function __construct(string $url) {
            $this -> url = $url;
        }

        public function getResponse(Request $request) : Response {
            if ($request -> getMethod() == 'GET') {
                $response = new TemporaryRedirectResponse($this -> url);
                $response -> setHeader('Cache-Control', 'private');
                return $response;
            }
            return new MethodNotAllowedResponse(['GET']);
        }
    }
}