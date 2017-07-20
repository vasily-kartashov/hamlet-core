<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;

/**
 * The provider could not be understood by the server due to malformed syntax. The client SHOULD NOT repeat the
 * provider without modifications.
 */
class BadRequestResponse extends Response
{
    public function __construct(Entity $entity = null)
    {
        parent::__construct(400, $entity);
    }
}
