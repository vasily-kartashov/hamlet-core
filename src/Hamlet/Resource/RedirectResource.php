<?php

namespace Hamlet\Resource;

use Hamlet\Request\RequestInterface;
use Hamlet\Response\MethodNotAllowedResponse;
use Hamlet\Response\TemporaryRedirectResponse;

class RedirectResource implements ResourceInterface
{
    protected $url;

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        assert(is_string($url));
        $this->url = $url;
    }

    /**
     * @param \Hamlet\Request\RequestInterface $request
     * @return \Hamlet\Response\MethodNotAllowedResponse|\Hamlet\Response\TemporaryRedirectResponse
     */
    public function getResponse(RequestInterface $request)
    {
        if ($request->getMethod() == 'GET') {
            $response = new TemporaryRedirectResponse($this->url);
            $response->setHeader('Cache-Control', 'private');
            return $response;
        }
        return new MethodNotAllowedResponse(['GET']);
    }
}
