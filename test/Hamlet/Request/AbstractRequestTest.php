<?php

namespace Hamlet\Request;

use UnitTestCase;

abstract class AbstractRequestTest extends UnitTestCase
{
    /**
     * Get request object
     *
     * @param string $path
     *
     * @return \Hamlet\Request\RequestInterface
     */
    abstract protected function createRequestFromPath($path);

    public function testPathMatches()
    {
        $request = $this->createRequestFromPath('/');
        $this->assertTrue($request->pathMatches('/'));

        $request = $this->createRequestFromPath('/test');
        $this->assertTrue($request->pathMatches('/test'));

        $request = $this->createRequestFromPath('/test/');
        $this->assertFalse($request->pathMatches('/'));

        $request = $this->createRequestFromPath('/привет из ниоткуда');
        $this->assertTrue($request->pathMatches('/привет из ниоткуда'));

        $request = $this->createRequestFromPath('/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82%20%D0%B8%D0%B7%20%D0%BD%D0%B8%D0%BE%D1%82%D0%BA%D1%83%D0%B4%D0%B0');
        $this->assertTrue($request->pathMatches('/привет из ниоткуда'));
    }

    public function testPathStartsWith()
    {
        $request = $this->createRequestFromPath('/hello');
        $this->assertTrue($request->pathStartsWith('/'));

        $request = $this->createRequestFromPath('/test?city=LON');
        $this->assertTrue($request->pathStartsWith('/test'));

        $request = $this->createRequestFromPath('/test');
        $this->assertTrue($request->pathStartsWith('/test'));

        $request = $this->createRequestFromPath('/привет из ниоткуда hello');
        $this->assertTrue($request->pathStartsWith('/привет из ниоткуда'));

        $request = $this->createRequestFromPath('/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82%20%D0%B8%D0%B7%20%D0%BD%D0%B8%D0%BE%D1%82%D0%BA%D1%83%D0%B4%D0%B0/?refresh');
        $this->assertTrue($request->pathStartsWith('/привет из ниоткуда'));
    }
}
