<?php

namespace Hamlet\Requests {

    use UnitTestCase;

    abstract class AbstractRequestTest extends UnitTestCase {

        abstract protected function createRequestFromPath($path) : Request;

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

        public function testPathMatchesPattern()
        {
            $request = $this->createRequestFromPath('/');
            $this->assertTrue($request->pathMatchesPattern('/'));
            $this->assertFalse($request->pathMatchesPattern('/*'));
            $this->assertFalse($request->pathMatchesPattern('/{name}'));

            $request = $this->createRequestFromPath('/de/home');
            $this->assertTrue($request->pathMatchesPattern('/*/*'));
            $this->assertFalse($request->pathMatchesPattern('/*'));
            $this->assertEqual(count($request->pathMatchesPattern('/{locale}/{template}')), 2);
            $this->assertTrue(is_bool($request->pathMatchesPattern('/*/*')));
        }

        public function testPathStartsWithPattern()
        {
            $request = $this->createRequestFromPath('/test?city=LON');
            $this->assertTrue($request->pathStartsWithPattern('/test'));
            $this->assertTrue($request->pathStartsWithPattern('/*'));
            $this->assertEqual($request->pathStartsWithPattern('/{template}')['template'], 'test');

            $request = $this->createRequestFromPath('/en/home');
            $this->assertTrue($request->pathStartsWithPattern('/en'));
            $this->assertEqual($request->pathStartsWithPattern('/{locale}')['locale'], 'en');
            $this->assertEqual($request->pathStartsWithPattern('/{locale}/home')['locale'], 'en');
            $this->assertTrue($request->pathStartsWithPattern('/*/home'));
            $this->assertTrue($request->pathStartsWithPattern('/en/*'));
            $this->assertTrue($request->pathStartsWithPattern('/*/*'));
            $this->assertFalse($request->pathStartsWithPattern('/en/'));
            $this->assertFalse($request->pathStartsWithPattern('/en/ho'));
        }
    }
}