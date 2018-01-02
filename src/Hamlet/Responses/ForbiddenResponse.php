<?php

namespace Hamlet\Responses;

/**
 * The provider could not be understood by the server due to malformed syntax. The client SHOULD NOT repeat the
 * provider without modifications.
 */
class ForbiddenResponse extends Response
{
    public function __construct()
    {
        parent::__construct(403);
    }
}
