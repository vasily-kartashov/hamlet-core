<?php

namespace Hamlet\Requests;

use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * Adapted from
 * https://github.com/zendframework/zend-diactoros/blob/master/test/RequestTest.php
 * https://github.com/zendframework/zend-diactoros/blob/master/test/ServerRequestTest.php
 */
class RequestZendTest extends TestCase
{
    public function testMethodIsGetByDefault()
    {
        $request = Request::empty();
        Assert::assertSame('GET', $request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request1 = Request::empty();
        $request2 = $request1->withMethod('POST');
        Assert::assertNotSame($request1, $request2);
        Assert::assertEquals('POST', $request2->getMethod());
    }

    public function testReturnsUnpopulatedUriByDefault()
    {
        $uri = Request::empty()->getUri();

        Assert::assertInstanceOf(UriInterface::class, $uri);
        Assert::assertInstanceOf(Uri::class, $uri);

        Assert::assertEmpty($uri->getScheme());
        Assert::assertEmpty($uri->getUserInfo());
        Assert::assertEmpty($uri->getHost());

        Assert::assertNull($uri->getPort());

        Assert::assertEmpty($uri->getPath());
        Assert::assertEmpty($uri->getQuery());
        Assert::assertEmpty($uri->getFragment());
    }

    public function testWithUriReturnsNewInstanceWithNewUri()
    {
        $request1 = Request::empty();

        $request2 = $request1->withUri(new Uri('https://example.com:10082/foo/bar?baz=bat'));
        Assert::assertNotSame($request1, $request2);

        $request3 = $request2->withUri(new Uri('/baz/bat?foo=bar'));
        Assert::assertNotSame($request1, $request2);
        Assert::assertNotSame($request2, $request3);

        Assert::assertSame('/baz/bat?foo=bar', (string) $request3->getUri());
    }

    public function testDefaultStreamIsWritable()
    {
        $request = Request::empty();
        $request->getBody()->write("test");
        Assert::assertSame("test", (string)$request->getBody());
    }

    public function customRequestMethods()
    {
        return [
            /* WebDAV methods */
            'TRACE'          => ['TRACE'],
            'PROPFIND'       => ['PROPFIND'],
            'PROPPATCH'      => ['PROPPATCH'],
            'MKCOL'          => ['MKCOL'],
            'COPY'           => ['COPY'],
            'MOVE'           => ['MOVE'],
            'LOCK'           => ['LOCK'],
            'UNLOCK'         => ['UNLOCK'],
            /* Arbitrary methods */
            '#!ALPHA-1234&%' => ['#!ALPHA-1234&%'],
        ];
    }

    /**
     * @dataProvider customRequestMethods
     * @param string $method
     */
    public function testAllowsCustomRequestMethodsThatFollowSpec(string $method)
    {
        $request = Request::empty()->withMethod($method);
        Assert::assertSame($method, $request->getMethod());
    }

    public function testRequestTargetIsSlashWhenNoUriPresent()
    {
        $request = Request::empty();
        Assert::assertSame('/', $request->getRequestTarget());
    }

    public function testRequestTargetIsSlashWhenUriHasNoPathOrQuery()
    {
        $request = Request::empty()->withUri(new Uri('http://example.com'));
        Assert::assertSame('/', $request->getRequestTarget());
    }

    public function requestsWithUri()
    {
        return [
            'absolute-uri' => [
                Request::empty()
                    ->withUri(new Uri('https://api.example.com/user'))
                    ->withMethod('POST'),
                '/user'
            ],
            'absolute-uri-with-query' => [
                Request::empty()
                    ->withUri(new Uri('https://api.example.com/user?foo=bar'))
                    ->withMethod('POST'),
                '/user?foo=bar'
            ],
            'relative-uri' => [
                Request::empty()
                    ->withUri(new Uri('/user'))
                    ->withMethod('GET'),
                '/user'
            ],
            'relative-uri-with-query' => [
                Request::empty()
                    ->withUri(new Uri('/user?foo=bar'))
                    ->withMethod('GET'),
                '/user?foo=bar'
            ],
        ];
    }

    /**
     * @dataProvider requestsWithUri
     * @param Request $request
     * @param string $expected
     */
    public function testReturnsRequestTargetWhenUriIsPresent(Request $request, string $expected)
    {
        Assert::assertSame($expected, $request->getRequestTarget());
    }

    public function validRequestTargets()
    {
        return [
            'asterisk-form' => ['*'],
            'authority-form' => ['api.example.com'],
            'absolute-form' => ['https://api.example.com/users'],
            'absolute-form-query' => ['https://api.example.com/users?foo=bar'],
            'origin-form-path-only' => ['/users'],
            'origin-form' => ['/users?id=foo'],
        ];
    }

    /**
     * @dataProvider validRequestTargets
     * @param string $requestTarget
     */
    public function testCanProvideARequestTarget(string $requestTarget)
    {
        $request = Request::empty()->withRequestTarget($requestTarget);
        Assert::assertSame($requestTarget, $request->getRequestTarget());
    }

    public function testRequestTargetCannotContainWhitespace()
    {
        $request = Request::empty();
        $this->expectException(InvalidArgumentException::class);
        $request->withRequestTarget('foo bar baz');
    }

    public function testRequestTargetDoesNotCacheBetweenInstances()
    {
        $request = Request::empty()->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        Assert::assertNotSame($original, $newRequest->getRequestTarget());
    }

    public function testSettingNewUriResetsRequestTarget()
    {
        $request = Request::empty()->withUri(new Uri('https://example.com/foo/bar'));
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        Assert::assertNotSame($request->getRequestTarget(), $newRequest->getRequestTarget());
    }

    public function testGetHeadersContainsHostHeaderIfUriWithHostIsPresent()
    {
        $request = Request::empty()->withUri(new Uri('http://example.com'));
        $headers = $request->getHeaders();
        Assert::assertArrayHasKey('Host', $headers);
        Assert::assertContains('example.com', $headers['Host']);
    }

    public function testGetHeadersContainsHostHeaderIfUriWithHostIsDeleted()
    {
        $request = $request = Request::empty()
            ->withUri(new Uri('http://www.example.com'))
            ->withoutHeader('host');
        $headers = $request->getHeaders();
        Assert::assertArrayHasKey('Host', $headers);
        Assert::assertContains('www.example.com', $headers['Host']);
    }

    public function testGetHeadersContainsNoHostHeaderIfNoUriPresent()
    {
        $request = Request::empty();
        $headers = $request->getHeaders();
        Assert::assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHeadersContainsNoHostHeaderIfUriDoesNotContainHost()
    {
        $request = Request::empty()->withUri(new Uri());
        $headers = $request->getHeaders();
        Assert::assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHostHeaderReturnsUriHostWhenPresent()
    {
        $request = Request::empty()->withUri(new Uri('http://www.example.com'));;
        $header = $request->getHeader('host');
        Assert::assertSame(['www.example.com'], $header);
    }

    public function testGetHostHeaderReturnsUriHostWhenHostHeaderDeleted()
    {
        $request = Request::empty()
            ->withUri(new Uri('http://www.example.com'))
            ->withoutHeader('host');
        $header = $request->getHeader('host');
        Assert::assertSame(['www.example.com'], $header);
    }

    public function testGetHostHeaderReturnsEmptyArrayIfNoUriPresent()
    {
        $request = Request::empty();
        Assert::assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderReturnsEmptyArrayIfUriDoesNotContainHost()
    {
        $request = Request::empty()->withUri(new Uri());
        Assert::assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderLineReturnsUriHostWhenPresent()
    {
        $request = Request::empty()->withUri(new Uri('http://www.example.com'));
        $header = $request->getHeaderLine('host');
        Assert::assertStringContainsString('example.com', $header);
    }

    public function testGetHostHeaderLineReturnsEmptyStringIfNoUriPresent()
    {
        $request = Request::empty();
        Assert::assertEmpty($request->getHeaderLine('host'));
    }

    public function testGetHostHeaderLineReturnsEmptyStringIfUriDoesNotContainHost()
    {
        $request = Request::empty()->withUri(new Uri());
        Assert::assertEmpty($request->getHeaderLine('host'));
    }

    public function testHostHeaderSetFromUriOnCreationIfNoHostHeaderSpecified()
    {
        $request = Request::empty()->withUri(new Uri('http://www.example.com'));
        Assert::assertTrue($request->hasHeader('Host'));
        Assert::assertSame('www.example.com', $request->getHeaderLine('host'));
    }

    public function testHostHeaderNotSetFromUriOnCreationIfHostHeaderSpecified()
    {
        $request = Request::empty()
            ->withUri(new Uri('http://www.example.com'))
            ->withHeader('Host', 'www.test.com');
        Assert::assertSame('www.test.com', $request->getHeaderLine('host'));
    }

    public function testPassingPreserveHostFlagWhenUpdatingUriDoesNotUpdateHostHeader()
    {
        $request = Request::empty()->withAddedHeader('Host', 'example.com');
        $uri = (new Uri())->withHost('www.example.com');
        $new = $request->withUri($uri, true);
        Assert::assertSame('example.com', $new->getHeaderLine('Host'));
    }

    public function testNotPassingPreserveHostFlagWhenUpdatingUriWithoutHostDoesNotUpdateHostHeader()
    {
        $request = Request::empty()->withAddedHeader('Host', 'example.com');
        $uri = new Uri();
        $new = $request->withUri($uri);
        Assert::assertSame('example.com', $new->getHeaderLine('Host'));
    }

    public function testHostHeaderUpdatesToUriHostAndPortWhenPreserveHostDisabledAndNonStandardPort()
    {
        $request = Request::empty()->withAddedHeader('Host', 'example.com');
        $uri = (new Uri())
            ->withHost('www.example.com')
            ->withPort(10081);
        $new = $request->withUri($uri);
        Assert::assertSame('www.example.com:10081', $new->getHeaderLine('Host'));
    }

    public function hostHeaderKeys()
    {
        return [
            'lowercase'         => ['host'],
            'mixed-4'           => ['hosT'],
            'mixed-3-4'         => ['hoST'],
            'reverse-titlecase' => ['hOST'],
            'uppercase'         => ['HOST'],
            'mixed-1-2-3'       => ['HOSt'],
            'mixed-1-2'         => ['HOst'],
            'titlecase'         => ['Host'],
            'mixed-1-4'         => ['HosT'],
            'mixed-1-2-4'       => ['HOsT'],
            'mixed-1-3-4'       => ['HoST'],
            'mixed-1-3'         => ['HoSt'],
            'mixed-2-3'         => ['hOSt'],
            'mixed-2-4'         => ['hOsT'],
            'mixed-2'           => ['hOst'],
            'mixed-3'           => ['hoSt'],
        ];
    }

    /**
     * @dataProvider hostHeaderKeys
     * @param string $hostKey
     */
    public function testWithUriAndNoPreserveHostWillOverwriteHostHeaderRegardlessOfOriginalCase(string $hostKey)
    {
        $request = Request::empty()->withHeader($hostKey, 'example.com');

        $uri = new Uri('http://example.org/foo/bar');
        $new = $request->withUri($uri);

        $host = $new->getHeaderLine('host');
        Assert::assertSame('example.org', $host);

        $headers = $new->getHeaders();
        Assert::assertArrayHasKey('Host', $headers);

        if ($hostKey !== 'Host') {
            Assert::assertArrayNotHasKey($hostKey, $headers);
        }
    }

    public function testServerParamsAreEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getServerParams());
    }

    public function testQueryParamsAreEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getQueryParams());
    }

    public function testQueryParamsMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];

        $request1 = Request::empty();
        $request2 = $request1->withQueryParams($value);

        Assert::assertNotSame($request1, $request2);
        Assert::assertSame($value, $request2->getQueryParams());
    }

    public function testCookiesAreEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getCookieParams());
    }

    public function testCookiesMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];

        $request1 = Request::empty();
        $request2 = $request1->withCookieParams($value);

        Assert::assertNotSame($request1, $request2);
        Assert::assertSame($value, $request2->getCookieParams());
    }

    public function testUploadedFilesAreEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getUploadedFiles());
    }

    public function testParsedBodyIsEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getParsedBody());
    }

    public function testParsedBodyMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];

        $request1 = Request::empty();
        $request2 = $request1->withParsedBody($value);

        Assert::assertNotSame($request1, $request2);
        Assert::assertSame($value, $request2->getParsedBody());
    }

    public function testAttributesAreEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getAttributes());
    }

    public function testSingleAttributesWhenEmptyByDefault()
    {
        Assert::assertEmpty(Request::empty()->getAttribute('does-not-exist'));
    }

    /**
     * @depends testAttributesAreEmptyByDefault
     */
    public function testAttributeMutatorReturnsCloneWithChanges()
    {
        $request1 = Request::empty();
        $request2 = $request1->withAttribute('foo', 'bar');

        Assert::assertNotSame($request1, $request2);
        Assert::assertSame('bar', $request2->getAttribute('foo'));

        return $request2;
    }

    /**
     * @depends testAttributeMutatorReturnsCloneWithChanges
     * @param Request $request
     */
    public function testRemovingAttributeReturnsCloneWithoutAttribute(Request $request)
    {
        $new = $request->withoutAttribute('foo');
        Assert::assertNotSame($request, $new);
        Assert::assertNull($new->getAttribute('foo', null));
    }

    public function testCookieParamsAreAnEmptyArrayAtInitialization()
    {
        $request = Request::empty();
        Assert::assertTrue(is_array($request->getCookieParams()));
        Assert::assertCount(0, $request->getCookieParams());
    }

    public function testQueryParamsAreAnEmptyArrayAtInitialization()
    {
        $request = Request::empty();
        Assert::assertTrue(is_array($request->getQueryParams()));
        Assert::assertCount(0, $request->getQueryParams());
    }

    public function testParsedBodyIsNullAtInitialization()
    {
        $request = Request::empty();
        Assert::assertNull($request->getParsedBody());
    }

    public function testAllowsRemovingAttributeWithNullValue()
    {
        $request = Request::empty();
        $request = $request->withAttribute('boo', null);
        $request = $request->withoutAttribute('boo');
        Assert::assertSame([], $request->getAttributes());
    }

    public function testAllowsRemovingNonExistentAttribute()
    {
        $request = Request::empty();
        $request = $request->withoutAttribute('boo');
        Assert::assertSame([], $request->getAttributes());
    }

    public function testTryToAddInvalidUploadedFiles()
    {
        $request = Request::empty();
        $this->expectException(InvalidArgumentException::class);
        $request->withUploadedFiles([null]);
    }

    public function testNestedUploadedFiles()
    {
        $request = Request::empty();
        $uploadedFiles = [
            new UploadedFile('php://temp', 0, 0),
            new UploadedFile('php://temp', 0, 0)
        ];
        $request = $request->withUploadedFiles($uploadedFiles);
        Assert::assertSame($uploadedFiles, $request->getUploadedFiles());
    }
}
