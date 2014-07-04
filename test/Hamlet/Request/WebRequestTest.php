<?php

namespace Hamlet\Request;

class WebRequestTest extends AbstractRequestTest
{
    public function createRequestFromPath($path)
    {
        if (isset($_SERVER)) {
            $_SERVER = [];
        }
        $oldValue = $_SERVER;
        $_SERVER = [
            'REQUEST_URI' => $path,
            'SERVER_NAME' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
        ];
        $request = new WebRequest();
        $_SERVER = $oldValue;
        return $request;
    }
}