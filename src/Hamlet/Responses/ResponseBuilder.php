<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;
use RuntimeException;

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
    private $sessionParams = [];

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

    public function withSessionParam(string $name, string $value): ResponseBuilder
    {
        $this->sessionParams[$name] = $value;
        return $this;
    }

    public function build(): Response
    {
        if ($this->statusCode == null) {
            throw new RuntimeException('Status code needs to be defined');
        }
        return new class($this->statusCode, $this->entity,  $this->entity !== null, $this->headers, $this->cookies, $this->sessionParams) extends Response
        {
            /**
             * @param int $statusCode
             * @param Entity|null $entity
             * @param bool $embedEntity
             * @param string[] $headers
             * @param Cookie[] $cookies
             * @param array $sessionParams
             */
            public function __construct($statusCode, $entity, $embedEntity, array $headers, array $cookies, array $sessionParams)
            {
                parent::__construct($statusCode, $entity, $embedEntity, $headers, $cookies, $sessionParams);
            }
        };
    }
}
