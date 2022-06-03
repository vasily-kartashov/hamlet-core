<?php

namespace Hamlet\Requests;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Hamlet\Cast\Type;
use Hamlet\Entities\Entity;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use SessionHandlerInterface;
use function array_key_exists;
use function count;
use function explode;
use function Hamlet\Cast\_class;
use function Hamlet\Cast\_map;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_string;
use function implode;
use function is_array;
use function strlen;
use function strtolower;
use function substr;
use function urldecode;

class Request implements ServerRequestInterface
{
    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @var string|null
     */
    private $path = null;

    /**
     * @var (callable():array<string,string|array<string>>)|null
     */
    private $headersProvider = null;

    /**
     * @var array<string|string,array<string>>|null
     */
    private $headers = null;

    /**
     * @var array<string,string>
     */
    private $headerNames = [];

    /**
     * @var (callable():UriInterface)|null
     */
    private $uriProvider = null;

    /**
     * @var UriInterface|null
     */
    private $uri = null;

    /**
     * @var string|null
     */
    private $requestTarget = null;

    /**
     * @var (callable():StreamInterface)|null
     */
    private $bodyProvider = null;

    /**
     * @var StreamInterface|null
     */
    private $body = null;

    /**
     * @var (callable():string)|null
     */
    private $protocolProvider = null;

    /**
     * @var string|null
     */
    private $protocol = null;

    /**
     * @var (callable():array<string,mixed>)|null
     */
    private $serverParamsProvider = null;

    /**
     * @var array<string,mixed>|null
     */
    private $serverParams = null;

    /**
     * @var (callable():array<string,mixed>)|null
     */
    private $cookieParamsProvider = null;

    /**
     * @var array<string,mixed>|null
     */
    private $cookieParams = null;

    /**
     * @var (callable():array<string,mixed>)|null
     */
    private $queryParamsProvider = null;

    /**
     * @var array<string,mixed>|null
     */
    private $queryParams = null;

    /**
     * @var (callable():(null|array|object))|null
     */
    private $parsedBodyProvider = null;

    /**
     * @var null|array|object
     */
    private $parsedBody = null;

    /**
     * @var bool
     */
    private $parsedBodySet = false;

    /**
     * @var (callable():array<UploadedFileInterface>)|null
     */
    private $uploadedFilesProvider = null;

    /**
     * @var array<string,UploadedFileInterface|array<string,mixed>>|null
     */
    private $uploadedFiles = null;

    /**
     * @var (callable():array<string,mixed>|null)|null
     */
    private $sessionParamsProvider = null;

    /**
     * @var array<string,mixed>|null
     */
    private $sessionParams = null;

    /**
     * @var bool
     */
    private $sessionParamsSet = false;

    /**
     * @var array<string,mixed>
     */
    private $attributes = [];

    private function __construct()
    {
    }

    public static function empty(): self
    {
        $request = new self;
        $request->uriProvider = function (): UriInterface {
            return new Uri('');
        };
        $request->headersProvider = function (): array {
            return [];
        };
        return $request;
    }

    public static function fromSuperGlobals(SessionHandlerInterface $sessionHandler = null): self
    {
        /**
         * @var array<string,mixed> $serverParams
         */
        $serverParams  = $_SERVER;
        $cookieParams  = $_COOKIE;
        $queryParams   = $_GET;
        $parsedBody    = $_POST;
        $uploadedFiles = $_FILES;

        $request = new self;

        $request->method = isset($serverParams['REQUEST_METHOD']) ? _string()->cast($serverParams['REQUEST_METHOD']) : 'GET';
        if (isset($serverParams['REQUEST_URI'])) {
            $request->path = strtok(_string()->cast($serverParams['REQUEST_URI']), '?') ?: null;
        }

        $request->headersProvider = function () use ($serverParams): array {
            return Normalizer::readHeadersFromSuperGlobals($serverParams);
        };
        $request->uriProvider = function () use ($serverParams): UriInterface {
            return Normalizer::getUriFromGlobals($serverParams);
        };
        $request->bodyProvider = function (): StreamInterface {
            return new LazyOpenStream('php://input', 'r+');
        };
        $request->protocolProvider = function () use ($serverParams): string {
            $protocol = isset($serverParams['SERVER_PROTOCOL']) ? _string()->cast($serverParams['SERVER_PROTOCOL']) : null;
            return Normalizer::extractVersion($protocol);
        };
        $request->serverParamsProvider = function () use ($serverParams): array {
            return $serverParams;
        };
        $request->cookieParamsProvider = function () use ($cookieParams): array {
            return $cookieParams;
        };
        $request->queryParamsProvider = function () use ($queryParams): array {
            return $queryParams;
        };
        $request->parsedBodyProvider = function () use ($parsedBody): array {
            return $parsedBody;
        };
        $request->uploadedFilesProvider = function () use ($uploadedFiles): array {
            return ServerRequest::normalizeFiles($uploadedFiles);
        };
        $request->sessionParamsProvider = function () use ($sessionHandler): ?array {
            if (session_status() == PHP_SESSION_NONE) {
                if ($sessionHandler !== null) {
                    session_set_save_handler($sessionHandler);
                }
                session_start();
            }
            return $_SESSION ?? null;
        };

        return $request;
    }

    public static function fromServerRequest(ServerRequestInterface $serverRequest, SessionHandlerInterface $sessionHandler = null): self
    {
        $request = new self;

        $request->method = $serverRequest->getMethod();

        $request->headersProvider = function () use ($serverRequest): array {
            return $serverRequest->getHeaders();
        };
        $request->uriProvider = function () use ($serverRequest): UriInterface {
            return $serverRequest->getUri();
        };
        $request->bodyProvider = function () use ($serverRequest): StreamInterface {
            return $serverRequest->getBody();
        };
        $request->protocolProvider = function () use ($serverRequest): string {
            return $serverRequest->getProtocolVersion();
        };
        $request->serverParamsProvider = function () use ($serverRequest): array {
            return $serverRequest->getServerParams();
        };
        $request->cookieParamsProvider = function () use ($serverRequest): array {
            return $serverRequest->getCookieParams();
        };
        $request->queryParamsProvider = function () use ($serverRequest): array {
            return $serverRequest->getQueryParams();
        };
        $request->parsedBodyProvider =
            /**
             * @return array|null|object
             */
            function () use ($serverRequest) {
                return $serverRequest->getParsedBody();
            };
        $request->uploadedFilesProvider = function () use ($serverRequest): array {
            return $serverRequest->getUploadedFiles();
        };
        $request->sessionParamsProvider = function () use ($serverRequest, $sessionHandler): ?array {
            if ($sessionHandler === null) {
                return null;
            }
            $sessionName = session_name();
            $cookies = $serverRequest->getCookieParams();
            if (isset($cookies[$sessionName])) {
                $sessionId = _string()->cast($cookies[session_name()]);
                $data = $sessionHandler->read($sessionId);
                if (!empty($data)) {
                    $sessionParams = unserialize($data);
                    assert(is_array($sessionParams));
                    return $sessionParams;
                }
            }
            return [];
        };

        return $request;
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string
    {
        if ($this->protocol === null) {
            if ($this->protocolProvider !== null) {
                $this->protocol = ($this->protocolProvider)();
            } else {
                $this->protocol = '1.1';
            }
        }
        return $this->protocol;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version): Request
    {
        $copy = clone $this;
        $copy->protocol = $version;
        return $copy;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        if ($this->headers === null) {
            $this->headers = [];
            if ($this->headersProvider !== null) {
                foreach (($this->headersProvider)() as $name => $value) {
                    $key = strtolower($name);
                    if (array_key_exists($key, $this->headerNames)) {
                        $name = $this->headerNames[$key];
                    } else {
                        $this->headerNames[$key] = $name;
                    }
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $this->headers[$name][] = $v;
                        }
                    } else {
                        $this->headers[$name][] = $value;
                    }
                }
            }
        }
        if (!array_key_exists('Host', $this->headers)) {
            $host = $this->getUri()->getHost();
            if ($host) {
                return ['Host' => [$host]] + $this->headers;
            }
        }
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name): bool
    {
        $key = strtolower($name);
        return array_key_exists($key, $this->headerNames);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return array<string> An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name): array
    {
        $this->getHeaders();
        assert($this->headers !== null);
        $key = strtolower($name);
        if (array_key_exists($key, $this->headerNames)) {
            return $this->headers[$this->headerNames[$key]];
        } elseif ($key == 'host') {
            $host = $this->getUri()->getHost();
            if ($host != '') {
                return [$host];
            }
        }
        return [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|array<string> $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value): Request
    {
        $this->getHeaders();

        $copy = clone $this;
        assert($copy->headers !== null);
        $key = strtolower($name);
        if (array_key_exists($key, $copy->headerNames)) {
            unset($copy->headerNames[$key]);
            unset($copy->headers[$key]);
        }
        if ($key == 'host') {
            $name = 'Host';
        }
        $copy->headerNames[$key] = $name;
        $copy->headers[$name] = [];
        if (is_array($value)) {
            foreach ($value as $v) {
                $copy->headers[$name][] = $v;
            }
        } else {
            $copy->headers[$name][] = $value;
        }
        return $copy;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value): Request
    {
        $this->getHeaders();

        $copy = clone $this;
        assert($copy->headers !== null);
        $key = strtolower($name);
        if (!array_key_exists($key, $copy->headerNames)) {
            $copy->headerNames[$key] = $name;
        } else {
            $name = $copy->headerNames[$key];
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                $copy->headers[$name][] = $v;
            }
        } else {
            $copy->headers[$name][] = $value;
        }
        return $copy;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): Request
    {
        $this->getHeaders();
        $key = strtolower($name);
        if (array_key_exists($key, $this->headerNames)) {
            $copy = clone $this;
            assert($copy->headers !== null);
            unset($copy->headers[$copy->headerNames[$key]]);
            unset($copy->headerNames[$key]);
            return $copy;
        } else {
            return $this;
        }
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            if ($this->bodyProvider !== null) {
                $this->body = ($this->bodyProvider)();
            } else {
                $this->body = Utils::streamFor(null);
            }
        }
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): Request
    {
        $copy = clone $this;
        $copy->body = $body;
        return $copy;
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     * @psalm-suppress DocblockTypeContradiction
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget === null) {
            $uri = $this->getUri();
            if ($uri == '') {
                $target = '/';
            } else {
                $target = $uri->getPath();
                if (empty($target)) {
                    $target = '/';
                }
                $query = $uri->getQuery();
                if ($query !== '') {
                    $target .= '?' . $query;
                }
            }
            $this->requestTarget = $target;
        }
        return $this->requestTarget;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget): Request
    {
        if (is_string($requestTarget) && preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Request target cannot contain whitespace');
        }

        if ($this->requestTarget === $requestTarget) {
            return $this;
        } else {
            $copy = clone $this;
            $copy->requestTarget = $requestTarget;
            return $copy;
        }
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method): Request
    {
        $copy = clone $this;
        $copy->method = $method;
        return $copy;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        if ($this->uri === null) {
            if ($this->uriProvider !== null) {
                $this->uri = ($this->uriProvider)();
            } else {
                $this->uri = new Uri();
            }
        }
        return $this->uri;
    }

    private function getPath(): string
    {
        if ($this->path === null) {
            $this->path = $this->getUri()->getPath();
        }
        return $this->path;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): Request
    {
        if ($uri === $this->getUri()) {
            return $this;
        }
        $copy = clone $this;
        $copy->uri = $uri;
        $copy->path = null;
        $copy->requestTarget = null;
        if (!$preserveHost || !$this->hasHeader('Host')) {
            $copy->updateHostFromUri();
        }
        return $copy;
    }

    /**
     * @return void
     */
    private function updateHostFromUri()
    {
        $uri = $this->getUri();
        $host = $uri->getHost();
        if (empty($host)) {
            return;
        }

        $port = $uri->getPort();
        if ($port !== null) {
            $host .= ':' . $port;
        }

        $this->getHeaders();

        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        assert($this->headers !== null);
        $this->headers = [$header => [$host]] + $this->headers;
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP`s $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams(): array
    {
        if ($this->serverParams === null) {
            if ($this->serverParamsProvider !== null) {
                $this->serverParams = ($this->serverParamsProvider)();
            } else {
                $this->serverParams = [];
            }
        }
        return $this->serverParams;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        if ($this->cookieParams === null) {
            if ($this->cookieParamsProvider !== null) {
                $this->cookieParams = ($this->cookieParamsProvider)();
            } else {
                $this->cookieParams = [];
            }
        }
        return $this->cookieParams;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies): Request
    {
        $copy = clone $this;
        $copy->cookieParams = _map(_string(), _mixed())->cast($cookies);
        return $copy;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        if ($this->queryParams === null) {
            if ($this->queryParamsProvider !== null) {
                $this->queryParams = ($this->queryParamsProvider)();
            } else {
                $this->queryParams = [];
            }
        }
        return $this->queryParams;
    }

    public function hasQueryParam(string $name): bool
    {
        return isset($this->getQueryParams()[$name]);
    }

    /**
     * @param string $name
     * @param string|null $default
     * @return string|null
     */
    public function getQueryParam(string $name, string $default = null): ?string
    {
        $queryParams = $this->getQueryParams();
        if (isset($queryParams[$name])) {
            return (string) $queryParams[$name];
        }
        return $default;
    }

    /**
     * @template T
     * @param string $name
     * @param Type $type
     * @psalm-param Type<T> $type
     * @return mixed
     * @psalm-return T
     */
    public function getTypedQueryParam(string $name, Type $type)
    {
        return $type->cast($this->getQueryParam($name));
    }

    public function hasBodyParam(string $name): bool
    {
        $body = $this->getParsedBody();
        return is_array($body) && array_key_exists($name, $body);
    }

    /**
     * @template T
     * @param string $name
     * @param Type $type
     * @psalm-param Type<T> $type
     * @return mixed
     * @psalm-return T
     */
    public function getTypedBodyParam(string $name, Type $type)
    {
        $body = $this->getParsedBody();
        assert(is_array($body));
        return $type->cast($body[$name]);
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP`s
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP`s parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query): Request
    {
        $copy = clone $this;
        $copy->queryParams = _map(_string(), _mixed())->cast($query);
        return $copy;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array
    {
        if ($this->uploadedFiles === null) {
            if ($this->uploadedFilesProvider !== null) {
                $this->uploadedFiles = ($this->uploadedFilesProvider)();
            } else {
                $this->uploadedFiles = [];
            }
        }
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles): Request
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedFunctionCall
         */
        $validate = function ($uploadedFiles) use (&$validate): bool {
            if (!is_array($uploadedFiles)) {
                return false;
            }
            foreach ($uploadedFiles as $value) {
                if (!_class(UploadedFileInterface::class)->matches($value) && !$validate($value)) {
                    return false;
                }
            }
            return true;
        };
        if (!$validate($uploadedFiles)) {
            throw new InvalidArgumentException('Uploaded files must implement UploadedFileInterface');
        }
        $copy = clone $this;
        $copy->uploadedFiles = $uploadedFiles;
        return $copy;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        if (!$this->parsedBodySet) {
            if ($this->parsedBodyProvider !== null) {
                $this->parsedBody = ($this->parsedBodyProvider)();
            }
            $this->parsedBodySet = true;
        }
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data): Request
    {
        if ($this->getParsedBody() !== $data) {
            $copy = clone $this;
            $copy->parsedBody = $data;
            $copy->parsedBodySet = true;
            return $copy;
        } else {
            return $this;
        }
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        } else {
            return $default;
        }
    }

    /**
     * @template T
     * @param string $name
     * @param Type $type
     * @psalm-param Type<T> $type
     * @return mixed
     * @psalm-return T
     */
    public function getTypedAttribute(string $name, Type $type)
    {
        return $type->cast($this->getAttribute($name));
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value): Request
    {
        if (array_key_exists($name, $this->attributes)) {
            if ($this->attributes[$name] === $value) {
                return $this;
            }
        }

        $copy = clone $this;
        $copy->attributes[$name] = $value;
        return $copy;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name): Request
    {
        if (array_key_exists($name, $this->attributes)) {
            $copy = clone $this;
            unset($copy->attributes[$name]);
            return $copy;
        } else {
            return $this;
        }
    }

    public function sessionStarted(): bool
    {
        return session_status() != PHP_SESSION_NONE;
    }

    /**
     * @return array|null
     */
    public function getSessionParams(): ?array
    {
        if (!$this->sessionParamsSet) {
            if ($this->sessionParamsProvider !== null) {
                $this->sessionParams = ($this->sessionParamsProvider)();
            } else {
                $this->sessionParams = null;
            }
            $this->sessionParamsSet = true;
        }
        return $this->sessionParams;
    }

    /**
     * @param array<string,mixed> $session
     * @return $this
     */
    public function withSessionParams(array $session): self
    {
        $copy = clone $this;
        $copy->sessionParams = $session;
        $copy->sessionParamsSet = true;
        return $copy;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getSessionParam(string $name, $default = null)
    {
        return $this->getSessionParams()[$name] ?? $default;
    }

    public function hasSessionParam(string $name): bool
    {
        return isset($this->getSessionParams()[$name]);
    }

    /**
     * Compare path tokens side by side. Returns false if no match, true if match without capture,
     * and array with matched tokens if used with capturing pattern
     *
     * @param string[] $pathTokens
     * @param string[] $patternTokens
     *
     * @return string[]|bool
     * @psalm-return array<string,string>|bool
     */
    protected function matchTokens(array $pathTokens, array $patternTokens)
    {
        $matches = [];
        for ($i = 1; $i < count($patternTokens); $i++) {
            $pathToken = $pathTokens[$i];
            $patternToken = $patternTokens[$i];
            if ($pathToken == '' && $patternToken != '') {
                return false;
            }
            if ($patternToken == '*') {
                continue;
            }
            if (substr($patternToken, 0, 1) == '{') {
                $matches[substr($patternToken, 1, -1)] = urldecode($pathToken);
            } else if (urldecode($pathToken) != $patternToken) {
                return false;
            }
        }
        if (empty($matches)) {
            return true;
        }
        return $matches;
    }

    public function pathMatches(string $path): bool
    {
        return $this->getPath() == $path;
    }

    /**
     * @param string $pattern
     * @return string[]|bool
     */
    public function pathMatchesPattern(string $pattern)
    {
        $pathTokens = explode('/', $this->getPath());
        $patternTokens = explode('/', $pattern);
        if (count($pathTokens) != count($patternTokens)) {
            return false;
        }
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function pathStartsWith(string $prefix): bool
    {
        $length = strlen($prefix);
        return substr($this->getPath(), 0, $length) == $prefix;
    }

    /**
     * @param string $pattern
     * @return string[]|bool
     */
    public function pathStartsWithPattern(string $pattern)
    {
        $pathTokens = explode('/', $this->getPath());
        $patternTokens = explode('/', $pattern);
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    /**
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-Match
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-None-Match
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-Modified-Since
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/If-Unmodified-Since
     * @param Entity $entity
     * @param CacheItemPoolInterface $cache
     * @return bool
     */
    public function preconditionFulfilled(Entity $entity, CacheItemPoolInterface $cache): bool
    {
        $matchHeaders           = $this->getHeader('If-Match');
        $noneMatchHeaders       = $this->getHeader('If-None-Match');
        $modifiedSinceHeaders   = $this->getHeader('If-Modified-Since');
        $unmodifiedSinceHeaders = $this->getHeader('If-Unmodified-Since');

        if (empty($matchHeaders) && empty($noneMatchHeaders) && empty($modifiedSinceHeaders) && empty($unmodifiedSinceHeaders)) {
            return true;
        }

        $cacheEntry   = $entity->load($cache);
        $tag          = $cacheEntry->tag();
        $lastModified = $cacheEntry->modified();

        if (isset($matchHeaders[0])) {
            $matchHeader = $matchHeaders[0];
            if ($matchHeader == '*') {
                return true;
            }
            foreach (explode(',', $matchHeader) as $matchTag) {
                if ($tag == trim($matchTag)) {
                    return true;
                }
            }
        }

        if (isset($noneMatchHeaders[0])) {
            $noneMatchHeader = $noneMatchHeaders[0];
            if ($noneMatchHeader == '*') {
                return true;
            }
            $matchFound = false;
            foreach (explode(',', $noneMatchHeader) as $noneMatchTag) {
                if ($tag == $noneMatchTag) {
                    $matchFound = true;
                    break;
                }
            }
            if (!$matchFound) {
                return true;
            }
        }

        if (isset($modifiedSinceHeaders[0])) {
            $modifiedSinceHeader = $modifiedSinceHeaders[0];
            if ($lastModified > strtotime($modifiedSinceHeader)) {
                return true;
            }
        }

        if (isset($unmodifiedSinceHeaders[0])) {
            $unmodifiedSinceHeader = $unmodifiedSinceHeaders[0];
            if ($lastModified < strtotime($unmodifiedSinceHeader)) {
                return true;
            }
        }

        return false;
    }
}
