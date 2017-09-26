<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;

/**
 * The provider has been fulfilled and resulted in a new resource being created. The newly created resource can be
 * referenced by the URI(s) returned in the entity of the provider, with the most specific URI for the resource
 * given by a Location header field. The provider SHOULD include an entity containing a list of resource
 * characteristics and location(s) from which the user or user agent can choose the one most appropriate. The entity
 * format is specified by the media type given in the Content-Type header field. The origin server MUST create the
 * resource before returning the 201 status code. If the action cannot be carried out immediately, the server SHOULD
 * respond with 202 (Accepted) provider instead.
 *
 * A 201 provider MAY contain an ETag provider header field indicating the current value of the entity tag for the
 * requested variant just created.
 */
class CreatedResponse extends Response
{
    public function __construct(string $url, Entity $entity = null)
    {
        parent::__construct(201);
        $this->withHeader('Location', $url);
        if ($entity) {
            $this->withEntity($entity);
        }
    }
}
