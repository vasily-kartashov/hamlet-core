<?php

namespace Hamlet\Requests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Void\VoidCachePool;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hamlet\Cast\CastException;
use Hamlet\Entities\JsonEntity;
use PHPUnit\Framework\TestCase;
use stdClass;
use function Hamlet\Cast\_class;
use function Hamlet\Cast\_int;

class RequestTest extends TestCase
{
    public function testMatchTokens()
    {
        $request = Request::empty()
            ->withUri(new Uri('/world/russia/world-cup'));

        $tokens = $request->pathMatchesPattern('/world/{country}/world-cup');
        $this->assertEquals(['country' => 'russia'], $tokens);
    }

    public function testIfMatch()
    {
        $cache = new VoidCachePool();

        $entity1 = new JsonEntity('abc');
        $entity2 = new JsonEntity('def');

        $request = Request::empty()
            ->withHeader('If-Match', $entity1->load($cache)->tag());

        $this->assertTrue($request->preconditionFulfilled($entity1, $cache));
        $this->assertFalse($request->preconditionFulfilled($entity2, $cache));
    }

    public function testIfNoneMatch()
    {
        $cache = new VoidCachePool();

        $entity1 = new JsonEntity('abc');
        $entity2 = new JsonEntity('def');

        $request = Request::empty()
            ->withHeader('If-None-Match', $entity1->load($cache)->tag());

        $this->assertFalse($request->preconditionFulfilled($entity1, $cache));
        $this->assertTrue($request->preconditionFulfilled($entity2, $cache));
    }

    public function testIfModifiedSince()
    {
        $cache = new ArrayCachePool();

        $entity = new JsonEntity('abc');
        $modified = $entity->load($cache)->modified();

        $request1 = Request::empty()
            ->withHeader('If-Modified-Since', gmdate('D, d M Y H:i:s', $modified - 10) . ' GMT');

        $this->assertTrue($request1->preconditionFulfilled($entity, $cache));

        $request2 = Request::empty()
            ->withHeader('If-Modified-Since', gmdate('D, d M Y H:i:s', $modified + 10) . ' GMT');

        $this->assertFalse($request2->preconditionFulfilled($entity, $cache));
    }

    public function testIfUnmodifiedSince()
    {
        $cache = new ArrayCachePool();

        $entity = new JsonEntity('cde');
        $modified = $entity->load($cache)->modified();

        $request1 = Request::empty()
            ->withHeader('If-Unmodified-Since', gmdate('D, d M Y H:i:s', $modified + 10) . ' GMT');

        $this->assertTrue($request1->preconditionFulfilled($entity, $cache));

        $request2 = Request::empty()
            ->withHeader('If-Unmodified-Since', gmdate('D, d M Y H:i:s', $modified - 10) . ' GMT');

        $this->assertFalse($request2->preconditionFulfilled($entity, $cache));
    }

    public function testGetTypedQueryParam()
    {
        $request = Request::empty()
            ->withQueryParams(['id' => '22']);

        $id = $request->getTypedQueryParam('id', _int());
        $this->assertTrue(is_int($id));
        $this->assertSame(22, $id);
    }

    public function testGetTypedQueryParamThrowsExceptionIfCastNotPossible()
    {
        $request = Request::empty()
            ->withQueryParams(['id' => 'abc']);

        $this->expectException(CastException::class);
        $request->getTypedQueryParam('id', _class(DateTime::class));
    }

    public function testGetTypedBodyParam()
    {
        $request = Request::empty()
            ->withParsedBody(['id' => '22']);

        $id = $request->getTypedBodyParam('id', _int());
        $this->assertTrue(is_int($id));
        $this->assertSame(22, $id);
    }

    public function testGetTypedBodyParamThrowsExceptionIfCastNotPossible()
    {
        $request = Request::empty()
            ->withParsedBody(['id' => new stdClass()]);

        $this->expectException(CastException::class);
        $request->getTypedQueryParam('id', _class(DateTime::class));
    }

    public function testGetTypedAttribute()
    {
        $request = Request::empty()->withAttribute('value', '123');
        $this->assertSame(123, $request->getTypedAttribute('value', _int()));
    }
}
