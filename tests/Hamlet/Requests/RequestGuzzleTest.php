<?php

namespace Hamlet\Requests;

use GuzzleHttp\Psr7\FnStream;
use InvalidArgumentException;
use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Adapted from:
 * https://github.com/guzzle/psr7/blob/master/tests/RequestTest.php
 * https://github.com/guzzle/psr7/blob/master/tests/ServerRequestTest.php
 */
class RequestGuzzleTest extends TestCase
{
    public function testConstructorDoesNotReadStreamBody()
    {
        $streamIsRead = false;
        $body = FnStream::decorate(stream_for(''), [
            '__toString' => function () use (&$streamIsRead) {
                $streamIsRead = true;
                return '';
            }
        ]);
        $r = Request::empty()->withBody($body);
        Assert::assertFalse($streamIsRead);
        Assert::assertSame($body, $r->getBody());
    }

    public function testWithMethodPreservesCase()
    {
        $request = Request::empty()->withMethod('get');
        Assert::assertEquals('get', $request->getMethod());
    }

    public function testWithUri()
    {
        $request1 = Request::empty();
        $uri1 = $request1->getUri();
        $uri2 = new Uri('http://www.example.com');
        $request2 = $request1->withUri($uri2);

        Assert::assertNotSame($request1, $request2);
        Assert::assertSame($uri2, $request2->getUri());
        Assert::assertSame($uri1, $request1->getUri());
    }

    public function testSameInstanceWhenSameUri()
    {
        $request1 = Request::empty()->withUri(new Uri('http://foo.com'));
        $request2 = $request1->withUri($request1->getUri());
        Assert::assertSame($request1, $request2);
    }

    public function testWithRequestTarget()
    {
        $request1 = Request::empty();
        $request2 = $request1->withRequestTarget('*');
        Assert::assertEquals('*', $request2->getRequestTarget());
        Assert::assertEquals('/', $request1->getRequestTarget());
    }

    public function testRequestTargetDoesNotAllowSpaces()
    {
        $this->expectException(InvalidArgumentException::class);
        Request::empty()->withRequestTarget('/foo bar');
    }

    public function testRequestTargetDefaultsToSlash()
    {
        $request1 = Request::empty();
        Assert::assertEquals('/', $request1->getRequestTarget());

        $request2 = Request::empty()->withUri(new Uri(''));
        Assert::assertEquals('/', $request2->getRequestTarget());

        $request3 = Request::empty()->withUri(new Uri('*'));
        Assert::assertEquals('*', $request3->getRequestTarget());

        $request4 = Request::empty()->withUri(new Uri('http://foo.com/bar baz/'));
        Assert::assertEquals('/bar%20baz/', $request4->getRequestTarget());
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
            'Foo'  => ['Bar']
        ], $request->getHeaders());
    }

    public function testCanGetHeaderAsCsv()
    {
        $request = Request::empty()
            ->withUri(new Uri('http://foo.com/baz?bar=bam'))
            ->withHeader('Foo', ['a', 'b', 'c']);

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
        Assert::assertEquals(['Host' => ['foo.com']], $request1->getHeaders());

        $request2 = $request1->withUri(new Uri('http://www.baz.com/bar'));
        Assert::assertEquals('www.baz.com', $request2->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders()
    {
        $request = Request::empty()
            ->withHeader('ZOO', 'zoobar')
            ->withAddedHeader('zoo', ['foobar', 'zoobar']);

        Assert::assertEquals(['ZOO' => ['zoobar', 'foobar', 'zoobar']], $request->getHeaders());
        Assert::assertEquals('zoobar, foobar, zoobar', $request->getHeaderLine('zoo'));
    }

    public function testAddsPortToHeader()
    {
        $r = Request::empty()->withUri(new Uri('http://foo.com:8124/bar'));
        Assert::assertEquals('foo.com:8124', $r->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort()
    {
        $request1 = Request::empty()->withUri(new Uri('http://foo.com:8124/bar'));
        $request2 = $request1->withUri(new Uri('http://foo.com:8125/bar'));

        Assert::assertEquals('foo.com:8124', $request1->getHeaderLine('host'));
        Assert::assertEquals('foo.com:8125', $request2->getHeaderLine('host'));
    }

    public function dataGetUriFromGlobals()
    {
        $server = [
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
            'SERVER_PORT' => '443',
            'SERVER_ADDR' => '217.112.82.20',
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'POST',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'HTTP_HOST' => 'www.example.org',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_PORT' => '5390',
            'SCRIPT_NAME' => '/blog/article.php',
            'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
            'PHP_SELF' => '/blog/article.php',
        ];
        return [
            'HTTPS request' => [
                'https://www.example.org/blog/article.php?id=10&user=foo',
                $server,
            ],
            'HTTPS request with different on value' => [
                'https://www.example.org/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTPS' => '1']),
            ],
            'HTTP request' => [
                'http://www.example.org/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTPS' => 'off', 'SERVER_PORT' => '80']),
            ],
            'HTTP_HOST missing -> fallback to SERVER_NAME' => [
                'https://www.example.org/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_HOST' => null]),
            ],
            'HTTP_HOST and SERVER_NAME missing -> fallback to SERVER_ADDR' => [
                'https://217.112.82.20/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_HOST' => null, 'SERVER_NAME' => null]),
            ],
            'Query string with ?' => [
                'https://www.example.org/path?continue=https://example.com/path?param=1',
                array_merge($server, ['REQUEST_URI' => '/path?continue=https://example.com/path?param=1', 'QUERY_STRING' => '']),
            ],
            'No query String' => [
                'https://www.example.org/blog/article.php',
                array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '']),
            ],
            'Host header with port' => [
                'https://www.example.org:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['HTTP_HOST' => 'www.example.org:8324']),
            ],
            'Different port with SERVER_PORT' => [
                'https://www.example.org:8324/blog/article.php?id=10&user=foo',
                array_merge($server, ['SERVER_PORT' => '8324']),
            ],
            'REQUEST_URI missing query string' => [
                'https://www.example.org/blog/article.php?id=10&user=foo',
                array_merge($server, ['REQUEST_URI' => '/blog/article.php']),
            ],
            'Empty server variable' => [
                'http://localhost',
                [],
            ],
        ];
    }

    /**
     * @dataProvider dataGetUriFromGlobals
     * @param $expected
     * @param $serverParams
     */
    public function testGetUriFromGlobals($expected, $serverParams)
    {
        Assert::assertEquals(new Uri($expected), Normalizer::getUriFromGlobals($serverParams));
    }

    public function testFromGlobals()
    {
        $_SERVER = [
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
            'SERVER_PORT' => '443',
            'SERVER_ADDR' => '217.112.82.20',
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'POST',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'CONTENT_TYPE' => 'text/plain',
            'HTTP_HOST' => 'www.example.org',
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_REFERRER' => 'https://example.com',
            'HTTP_USER_AGENT' => 'My User Agent',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_PORT' => '5390',
            'SCRIPT_NAME' => '/blog/article.php',
            'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
            'PHP_SELF' => '/blog/article.php',
        ];
        $_COOKIE = [
            'logged-in' => 'yes!'
        ];
        $_POST = [
            'name' => 'Pesho',
            'email' => 'pesho@example.com',
        ];
        $_GET = [
            'id' => 10,
            'user' => 'foo',
        ];
        $_FILES = [
            'file' => [
                'name' => 'MyFile.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/php/php1h4j1o',
                'error' => UPLOAD_ERR_OK,
                'size' => 123,
            ]
        ];
        $server = Request::fromSuperGlobals();
        Assert::assertSame('POST', $server->getMethod());
        Assert::assertEquals([
            'Host' => ['www.example.org'],
            'Content-Type' => ['text/plain'],
            'Accept' => ['text/html'],
            'Referrer' => ['https://example.com'],
            'User-Agent' => ['My User Agent'],
        ], $server->getHeaders());
        Assert::assertSame('', (string)$server->getBody());
        Assert::assertSame('1.1', $server->getProtocolVersion());
        Assert::assertEquals($_COOKIE, $server->getCookieParams());
        Assert::assertEquals($_POST, $server->getParsedBody());
        Assert::assertEquals($_GET, $server->getQueryParams());
        Assert::assertEquals(
            new Uri('https://www.example.org/blog/article.php?id=10&user=foo'),
            $server->getUri()
        );
        $expectedFiles = [
            'file' => new UploadedFile(
                '/tmp/php/php1h4j1o',
                123,
                UPLOAD_ERR_OK,
                'MyFile.txt',
                'text/plain'
            ),
        ];
        Assert::assertEquals($expectedFiles, $server->getUploadedFiles());
    }

    public function testUploadedFiles()
    {
        $request1 = Request::empty();
        $files = [
            'file' => new UploadedFile('test', 123, UPLOAD_ERR_OK)
        ];
        $request2 = $request1->withUploadedFiles($files);
        Assert::assertNotSame($request2, $request1);
        Assert::assertSame([], $request1->getUploadedFiles());
        Assert::assertSame($files, $request2->getUploadedFiles());
    }

    public function testCookieParams()
    {
        $request1 = Request::empty();
        $params = ['name' => 'value'];
        $request2 = $request1->withCookieParams($params);
        Assert::assertNotSame($request2, $request1);
        Assert::assertEmpty($request1->getCookieParams());
        Assert::assertSame($params, $request2->getCookieParams());
    }

    public function testQueryParams()
    {
        $request1 = Request::empty();
        $params = ['name' => 'value'];
        $request2 = $request1->withQueryParams($params);
        Assert::assertNotSame($request2, $request1);
        Assert::assertEmpty($request1->getQueryParams());
        Assert::assertSame($params, $request2->getQueryParams());
    }

    public function testParsedBody()
    {
        $request1 = Request::empty();
        $params = ['name' => 'value'];
        $request2 = $request1->withParsedBody($params);
        Assert::assertNotSame($request2, $request1);
        Assert::assertEmpty($request1->getParsedBody());
        Assert::assertSame($params, $request2->getParsedBody());
    }

    public function testAttributes()
    {
        $request1 = Request::empty();

        $request2 = $request1->withAttribute('name', 'value');
        $request3 = $request2->withAttribute('other', 'otherValue');
        $request4 = $request3->withoutAttribute('other');
        $request5 = $request3->withoutAttribute('unknown');
        Assert::assertNotSame($request2, $request1);
        Assert::assertNotSame($request3, $request2);
        Assert::assertNotSame($request4, $request3);
        Assert::assertSame($request5, $request3);
        Assert::assertSame([], $request1->getAttributes());
        Assert::assertNull($request1->getAttribute('name'));
        Assert::assertSame(
            'something',
            $request1->getAttribute('name', 'something'),
            'Should return the default value'
        );
        Assert::assertSame('value', $request2->getAttribute('name'));
        Assert::assertSame(['name' => 'value'], $request2->getAttributes());
        Assert::assertEquals(['name' => 'value', 'other' => 'otherValue'], $request3->getAttributes());
        Assert::assertSame(['name' => 'value'], $request4->getAttributes());
    }

    public function testNullAttribute()
    {
        $request = Request::empty()->withAttribute('name', null);
        Assert::assertSame(['name' => null], $request->getAttributes());
        Assert::assertNull($request->getAttribute('name', 'different-default'));

        $requestWithoutAttribute = $request->withoutAttribute('name');
        Assert::assertSame([], $requestWithoutAttribute->getAttributes());
        Assert::assertSame('different-default', $requestWithoutAttribute->getAttribute('name', 'different-default'));
    }
}
