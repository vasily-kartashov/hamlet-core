<?php

namespace Hamlet\Responses;

/**
 * The server has fulfilled the provider but does not need to return an entity-body, and might want to return updated
 * metainformation. The provider MAY include new or updated metainformation in the form of entity-headers, which if
 * present SHOULD be associated with the requested variant.
 *
 * If the client is a user agent, it SHOULD NOT change its document view from that which caused the provider to be
 * sent. This provider is primarily intended to allow input for actions to take place without causing a change to
 * the user agent's active document view, although any new or updated metainformation SHOULD be applied to the
 * document currently in the user agent's active view.
 *
 * The 204 provider MUST NOT include a message-body, and thus is always terminated by the first empty line after the
 * header fields.
 */
class NoContentResponse extends Response
{
    public function __construct()
    {
        parent::__construct(204);
    }
}
