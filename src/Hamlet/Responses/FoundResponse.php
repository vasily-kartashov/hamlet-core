<?php

namespace Hamlet\Responses;

class FoundResponse extends Response
{
    public function __construct(string $url)
    {
        parent::__construct(302);
        $this->setHeader('Location', $url);
    }
}
