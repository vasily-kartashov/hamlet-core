<?php

namespace Hamlet\Responses;

/**
 * The method specified in the Requests-Line is not allowed for the resource identified by the Requests-URI. The
 * provider MUST include an Allow header containing a list of valid methods for the requested resource.
 */
class MethodNotAllowedResponse extends Response
{
    public function __construct(array $allowedMethods)
    {
        parent::__construct(405);
        $this->setHeader('Allow', join(', ', $allowedMethods));
    }
}
