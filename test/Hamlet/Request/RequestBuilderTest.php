<?php

namespace Hamlet\Request;

class RequestBuilderTest extends AbstractRequestTest
{
    protected function createRequestFromPath($path)
    {
        $requestBuilder = new RequestBuilder();
        return $requestBuilder->setPath($path)->getRequest();
    }

    /**
     * @param $json
     * @return RequestInterface
     */
    protected  function createRequestFromJSON($json)
    {
        $requestBuilder = new RequestBuilder();
        return $requestBuilder->parseJSON($json)->getRequest();
    }

    public function testSetPath()
    {
        $requestBuilder = new RequestBuilder();
        $request = $requestBuilder->setPath('/test?a=1')->getRequest();

        $this->assertEqual($request->getParameter('a'), 1);

        $body = 'body';
        $cookies = [
            'cookie1' => 'cookie1data',
            'cookie2' => 'cookie2data',
            'cookie3' => 'cookie3data'
        ];
        $environmentName = 'test';
        $headers = [
            'header1' => 'header1data',
            'header2' => 'header2data',
            'header3' => 'header3data'
        ];
        $ip = '192.168.0.1';
        $method = 'POST';
        $path = '/testjson';
        $parameters = [
            'parameter1' => 'parameter1data',
            'parameter2' => 'parameter2data',
            'parameter3' => 'parameter3data'
        ];
        $sessionParameters = [
            'sessionParameter1' => 'sessionParameter1data',
            'sessionParameter2' => 'sessionParameter2data',
            'sessionParameter3' => 'sessionParameter3data'
        ];
        $host = 'localhost';

        $data = [
            'body' => $body,
            'cookies' => $cookies,
            'environmentName' => $environmentName,
            'headers' => $headers,
            'ip' => $ip,
            'method' => $method,
            'path' => $path,
            'parameters' => $parameters,
            'sessionParameters' => $sessionParameters,
            'host' => $host
        ];
        $json = json_encode($data);
        $request = $this->createRequestFromJSON($json);

        $this->assertEqual($request->getBody(),$body);
        $this->assertEqual($request->getEnvironmentName(),$environmentName);
        $this->assertEqual($request->getCookie('cookie1'),$cookies['cookie1']);
        $this->assertEqual($request->getHeader('header1'),$headers['header1']);
        $this->assertEqual($request->getRemoteIpAddress(),$ip);
        $this->assertEqual($request->getMethod(),$method);
        $this->assertEqual($request->getParameter('parameter1'),$parameters['parameter1']);
        $this->assertEqual($request->getSessionParameter('sessionParameter1'),$sessionParameters['sessionParameter1']);

        // test serialize method too
        $this->assertEqual($request->jsonSerialize(), $data);


    }
}