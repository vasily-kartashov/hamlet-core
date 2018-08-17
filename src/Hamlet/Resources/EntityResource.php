<?php

namespace Hamlet\Resources;

use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;
use Hamlet\Responses\Response;
use Hamlet\Responses\MethodNotAllowedResponse;
use Hamlet\Responses\OKOrNotModifiedResponse;

class EntityResource implements WebResource
{
    /** @var Entity */
    protected $entity;

    /** @var string[] */
    protected $methods;

    public function __construct(Entity $entity, string ... $methods)
    {
        $this->entity  = $entity;
        $this->methods = $methods ?: ['GET'];
    }

    public function getResponse(Request $request): Response
    {
        if (in_array($request->getMethod(), $this->methods)) {
            $response = new OKOrNotModifiedResponse($this->entity);
            return $response;
        }
        return new MethodNotAllowedResponse(... $this->methods);
    }
}
