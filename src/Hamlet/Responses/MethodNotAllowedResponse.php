<?php

namespace Hamlet\Responses;

/**
 * The method specified in the Requests-Line is not allowed for the resource identified by the Requests-URI. The
 * provider MUST include an Allow header containing a list of valid methods for the requested resource.
 */
class MethodNotAllowedResponse extends Response
{
    public function __construct(string ... $allowedMethods)
    {
        parent::__construct(405);
        $this->withHeader('Allow', join(', ', $allowedMethods));
    }
}
