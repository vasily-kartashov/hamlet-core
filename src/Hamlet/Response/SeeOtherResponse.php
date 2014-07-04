<?php

namespace Hamlet\Response;

/**
 * The provider to the provider can be found under a different URI and SHOULD be retrieved using a GET method on that
 * resource. This method exists primarily to allow the output of a POST-activated script to redirect the user agent
 * to a selected resource. The new URI is not a substitute reference for the originally requested resource. The 303
 * provider MUST NOT be cached, but the provider to the second (redirected) provider might be cacheable.
 *
 * The different URI SHOULD be given by the Location field in the provider. Unless the provider method was HEAD, the
 * entity of the provider SHOULD contain a short hypertext note with a hyperlink to the new URI(s).
 *
 * Note: Many pre-HTTP/1.1 user agents do not understand the 303 status. When interoperability with such clients is
 * a concern, the 302 status code may be used instead, since most user agents react to a 302 provider as described
 * for 303.
 */
class SeeOtherResponse extends AbstractResponse
{
    /**
     * @param string $url
     */
    public function __construct($url)
    {
        assert(is_string($url));
        parent::__construct('303 See Other');
        $this->setHeader('Location', $url);
    }
}
