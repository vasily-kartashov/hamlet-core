<?php

namespace Hamlet\Response;

/**
 * The request requires user authentication. The response MUST include a WWW-Authenticate header field containing a
 * challenge applicable to the requested resource. The client MAY repeat the request with a suitable Authorization
 * header field. If the request already included Authorization credentials, then the 401 response indicates that
 * authorization has been refused for those credentials. If the 401 response contains the same challenge as the
 * prior response, and the user agent has already attempted authentication at least once, then the user SHOULD be
 * presented the entity that was given in the response, since that entity might include relevant diagnostic
 * information.
 */
class UnauthorizedResponse extends AbstractResponse
{
    public function __construct(EntityInterface $entity = null)
    {
        parent::__construct('401 Unauthorized');
        if (!is_null($entity)) {
            $this->setEntity($entity);
        }
    }
}
