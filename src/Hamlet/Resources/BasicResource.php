<?php

namespace Hamlet\Resources;

use Hamlet\Requests\Request;
use Hamlet\Responses\Response;

class BasicResource implements WebResource
{
    protected $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse(Request $request): Response
    {
        return $this->response;
    }
}
