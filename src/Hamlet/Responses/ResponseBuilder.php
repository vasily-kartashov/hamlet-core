<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;

class ResponseBuilder
{
    private $statusCode;
    private $entity;
    private $headers = [];
    private $cookies = [];
    private $session = [];

    private function __construct()
    {
    }

    public static function create(): ResponseBuilder
    {
        return new ResponseBuilder();
    }

    public function withStatusCode(int $code): ResponseBuilder
    {
        $this->statusCode = $code;
        return $this;
    }

    public function withEntity(Entity $entity): ResponseBuilder
    {
        $this->entity = $entity;
        return $this;
    }

    public function withHeader(string $name, string $value): ResponseBuilder
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function withCookie(Cookie $cookie): ResponseBuilder
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    public function withSessionParameter(string $name, string $value): ResponseBuilder
    {
        $this->session[$name] = $value;
        return $this;
    }

    public function build(): Response
    {
        return new class($this->statusCode, $this->entity,  !is_null($this->entity), $this->headers, $this->cookies, $this->session) extends Response
        {
            public function __construct($statusCode, $entity, $embedEntity, array $headers, array $cookies, array $session)
            {
                parent::__construct($statusCode, $entity, $embedEntity, $headers, $cookies, $session);
            }
        };
    }
}
