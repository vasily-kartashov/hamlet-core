<?php

namespace Hamlet\Response;

class PreconditionFailedResponse extends AbstractResponse
{
    public function __construct()
    {
        parent::__construct('412 Precondition Failed');
    }
}
