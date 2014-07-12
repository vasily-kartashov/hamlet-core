<?php

namespace Hamlet\Request;

class RequestBuilderTest extends AbstractRequestTest
{
    protected function createRequestFromPath($path)
    {
        $requestBuilder = new RequestBuilder();
        return $requestBuilder->setPath($path)->getRequest();
    }

    public function testSetPath()
    {
        $requestBuilder = new RequestBuilder();
        $request = $requestBuilder->setPath('/test?a=1')->getRequest();

        $this->assertEqual($request->getParameter('a'), 1);
    }
}