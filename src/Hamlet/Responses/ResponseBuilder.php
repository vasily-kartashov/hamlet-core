<?php

namespace Hamlet\Responses;

use Exception;
use Hamlet\Entities\Entity;

class ResponseBuilder
{
    /** @var int|null */
    private $statusCode;

    /** @var Entity|null */
    private $entity;

    /** @var string[] */
    private $headers = [];

    /** @var Cookie[] */
    private $cookies = [];

    /** @var string[] */
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
        if ($this->statusCode == null) {
            throw new Exception('Status code needs to be defined');
        }
        return new class($this->statusCode, $this->entity,  !is_null($this->entity), $this->headers, $this->cookies, $this->session) extends Response
        {
            /**
             * @param int $statusCode
             * @param Entity|null $entity
             * @param bool $embedEntity
             * @param string[] $headers
             * @param Cookie[] $cookies
             * @param string[] $session
             */
            public function __construct($statusCode, $entity, $embedEntity, array $headers, array $cookies, array $session)
            {
                parent::__construct($statusCode, $entity, $embedEntity, $headers, $cookies, $session);
            }
        };
    }
}
