<?php

namespace Hamlet\Request;

class RequestBuilderTest extends AbstractRequestTest
{
    protected function createRequestFromPath($path)
    {
        $requestBuilder = new RequestBuilder();
        return $requestBuilder->setPath($path)->getRequest();
    }
}