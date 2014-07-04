<?php

namespace Hamlet\Response;

/**
 * In this case, the request should be repeated with another URI; however, future requests should still use the
 * original URI. In contrast to how 302 was historically implemented, the request method is not allowed to be
 * changed when reissuing the original request. For instance, a POST request should be repeated using another POST
 * request.
 */
class TemporaryRedirectResponse extends AbstractResponse
{
    /**
     * @param string $url
     */
    public function __construct($url)
    {
        assert(is_string($url));
        parent::__construct('307 Temporary Redirect');
        $this->setHeader('Location', $url);
    }
}
