<?php

namespace Hamlet\Resources;

use Hamlet\Requests\Request;
use Hamlet\Responses\MethodNotAllowedResponse;
use Hamlet\Responses\Response;
use Hamlet\Responses\TemporaryRedirectResponse;

class RedirectResource implements WebResource
{
    /** @var string */
    protected $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getResponse(Request $request): Response
    {
        if ($request->method() == 'GET') {
            return new TemporaryRedirectResponse($this->url);
        }
        return new MethodNotAllowedResponse('GET');
    }
}
