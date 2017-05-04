<?php

namespace Hamlet\Resources;

use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;
use Hamlet\Responses\MethodNotAllowedResponse;
use Hamlet\Responses\NotFoundResponse;
use Hamlet\Responses\Response;

class NotFoundResource implements WebResource
{
    protected $entity;

    public function __construct(Entity $entity = null)
    {
        $this->entity = $entity;
    }

    public function getResponse(Request $request): Response
    {
        if ($request->method() == 'GET') {
            return new NotFoundResponse($this->entity);
        }
        return new MethodNotAllowedResponse('GET');
    }
}
