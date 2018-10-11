<?php

namespace Hamlet\Requests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Void\VoidCachePool;
use GuzzleHttp\Psr7\Uri;
use Hamlet\Entities\JsonEntity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class RequestTest extends TestCase
{
    public function testConstructorDoesNotReadStreamBody()
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects($this->never())
            ->method('__toString');

        $request = Request::empty()->withBody($body);
        $this->assertSame($body, $request->getBody());
    }

    public function testWithUri()
    {
        $request1 = Request::empty()->withUri(new Uri('/'));
        $uri1 = $request1->getUri();

        $uri2 = new Uri('http://www.example.com');
        $request2 = $request1->withUri($uri2);

        Assert::assertNotSame($request1, $request2);
        Assert::assertSame($uri2, $request2->getUri());
        Assert::assertSame($uri1, $request1->getUri());
    }

    public function testSameInstanceWhenSameUri()
    {
        $uri = new Uri('http://foo.com');

        $request1 = Request::empty()->withUri($uri);
        $request2 = $request1->withUri($request1->getUri());

        Assert::assertSame($request1, $request2);
    }

    public function testWithRequestTarget()
    {
        $request1 = Request::empty()->withUri(new Uri('/'));
        $request2 = $request1->withRequestTarget('*');

        Assert::assertEquals('*', $request2->getRequestTarget());
        Assert::assertEquals('/', $request1->getRequestTarget());
    }

    public function testRequestTargetDefaultsToSlash()
    {
        $request1 = Request::empty();
        Assert::assertEquals('/', $request1->getRequestTarget());

        $request2 = Request::empty()->withUri(new Uri('*'));
        Assert::assertEquals('*', $request2->getRequestTarget());

        $request3 = Request::empty()->withUri(new Uri('http://foo.com/bar baz/'));
        Assert::assertEquals('/bar%20baz/', $request3->getRequestTarget());
    }

    public function testBuildsRequestTarget()
    {
        $request = Request::empty()->withUri(new Uri('http://foo.com/baz?bar=bam'));
        Assert::assertEquals('/baz?bar=bam', $request->getRequestTarget());
    }

    public function testBuildsRequestTargetWithFalseyQuery()
    {
        $request = Request::empty()->withUri(new Uri('http://foo.com/baz?0'));
        Assert::assertEquals('/baz?0', $request->getRequestTarget());
    }

    public function testHostIsAddedFirst()
    {
        $request = Request::empty()
            ->withUri(new Uri('http://foo.com/baz?bar=bam'))
            ->withHeader('Foo', 'Bar');

        Assert::assertEquals([
            'Host' => ['foo.com'],
            'Foo'  => ['Bar'],
        ], $request->getHeaders());
    }

    public function testCanGetHeaderAsCsv()
    {
        $request = Request::empty()->withHeader('Foo', ['a', 'b', 'c']);

        Assert::assertEquals('a, b, c', $request->getHeaderLine('Foo'));
        Assert::assertEquals('', $request->getHeaderLine('Bar'));
    }

    public function testHostIsNotOverwrittenWhenPreservingHost()
    {
        $request1 = Request::empty()
            ->withUri(new Uri('http://foo.com/baz?bar=bam'))
            ->withHeader('Host', 'a.com');
        Assert::assertEquals(['Host' => ['a.com']], $request1->getHeaders());

        $request2 = $request1->withUri(new Uri('http://www.foo.com/bar'), true);
        Assert::assertEquals('a.com', $request2->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri()
    {
        $request1 = Request::empty()->withUri(new Uri('http://foo.com/baz?bar=bam'));
        $this->assertEquals(['Host' => ['foo.com']], $request1->getHeaders());

        $request2 = $request1->withUri(new Uri('http://www.baz.com/bar'));
        $this->assertEquals('www.baz.com', $request2->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders()
    {
        $request = Request::empty()
            ->withAddedHeader('ZOO', 'zoobar')
            ->withAddedHeader('zoo', ['foobar', 'zoobar']);

        Assert::assertEquals(['ZOO' => ['zoobar', 'foobar', 'zoobar']], $request->getHeaders());
        Assert::assertEquals('zoobar, foobar, zoobar', $request->getHeaderLine('zoo'));
    }

    public function testAddsPortToHeader()
    {
        $request = Request::empty()->withUri(new Uri('http://foo.com:8124/bar'));
        Assert::assertEquals('foo.com:8124', $request->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort()
    {
        $request = Request::empty()->withUri(new Uri('http://foo.com:8124/bar'));
        $request = $request->withUri(new Uri('http://foo.com:8125/bar'));

        Assert::assertEquals('foo.com:8125', $request->getHeaderLine('host'));
    }

    public function testCanHaveHeaderWithEmptyValue()
    {
        $request = Request::empty()->withHeader('Foo', '');
        $this->assertEquals([''], $request->getHeader('Foo'));
    }

    public function testMatchTokens()
    {
        $request = Request::empty()
            ->withUri(new Uri('/world/russia/world-cup'));

        $tokens = $request->pathMatchesPattern('/world/{country}/world-cup');
        Assert::assertEquals(['country' => 'russia'], $tokens);
    }

    public function testIfMatch()
    {
        $cache = new VoidCachePool();

        $entity1 = new JsonEntity('abc');
        $entity2 = new JsonEntity('def');

        $request = Request::empty()
            ->withHeader('If-Match', $entity1->load($cache)->tag());

        Assert::assertTrue($request->preconditionFulfilled($entity1, $cache));
        Assert::assertFalse($request->preconditionFulfilled($entity2, $cache));
    }

    public function testIfNoneMatch()
    {
        $cache = new VoidCachePool();

        $entity1 = new JsonEntity('abc');
        $entity2 = new JsonEntity('def');

        $request = Request::empty()
            ->withHeader('If-None-Match', $entity1->load($cache)->tag());

        Assert::assertFalse($request->preconditionFulfilled($entity1, $cache));
        Assert::assertTrue($request->preconditionFulfilled($entity2, $cache));
    }

    public function testIfModifiedSince()
    {
        $cache = new ArrayCachePool();

        $entity = new JsonEntity('abc');
        $modified = $entity->load($cache)->modified();

        $request1 = Request::empty()
            ->withHeader('If-Modified-Since', gmdate('D, d M Y H:i:s', $modified - 10) . ' GMT');

        Assert::assertTrue($request1->preconditionFulfilled($entity, $cache));

        $request2 = Request::empty()
            ->withHeader('If-Modified-Since', gmdate('D, d M Y H:i:s', $modified + 10) . ' GMT');

        Assert::assertFalse($request2->preconditionFulfilled($entity, $cache));
    }

    public function testIfUnmodifiedSince()
    {
        $cache = new ArrayCachePool();

        $entity = new JsonEntity('cde');
        $modified = $entity->load($cache)->modified();

        $request1 = Request::empty()
            ->withHeader('If-Unmodified-Since', gmdate('D, d M Y H:i:s', $modified + 10) . ' GMT');

        Assert::assertTrue($request1->preconditionFulfilled($entity, $cache));

        $request2 = Request::empty()
            ->withHeader('If-Unmodified-Since', gmdate('D, d M Y H:i:s', $modified - 10) . ' GMT');

        Assert::assertFalse($request2->preconditionFulfilled($entity, $cache));
    }
}
