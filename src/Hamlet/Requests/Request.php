<?php

namespace Hamlet\Requests;

use DateTime;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;
use Hamlet\Entities\Entity;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class Request
{
    private $headers = [];
    private $queryParameters = [];
    private $parameters = [];
    private $body;
    private $sessionParameters = null;
    private $cookies = [];
    private $files = [];

    private $path;

    protected function __construct(
        array $headers,
        array $queryParameters,
        array $parameters,
        StreamInterface $body,
        callable $sessionParameters,
        array $cookies,
        array $files,
        array $serverParameters
    ) {
        $this->headers           = $headers;
        $this->queryParameters   = $queryParameters;
        $this->parameters        = $parameters;
        $this->body              = $body;
        $this->sessionParameters = $sessionParameters;
        $this->cookies           = $cookies;
        $this->files             = $files;
        $this->serverParameters  = $serverParameters;
    }

    public static function fromGlobals(): Request
    {
        $headers           = Request::readHeaders();
        $queryParameters   = $_GET;
        $parameters        = $_POST;
        $body              = new LazyOpenStream('php://input', 'r+');
        $sessionParameters = function() {
            if (!session_id()) {
                session_start();
            }
            return $_SESSION ?? [];
        };
        $cookies           = $_COOKIE;
        $files             = $_FILES;
        $serverParameters  = $_SERVER;

        return new Request(
            $headers,
            $queryParameters,
            $parameters,
            $body,
            $sessionParameters,
            $cookies,
            $files,
            $serverParameters
        );
    }

    public function toPsrRequest(): ServerRequestInterface
    {
        $psrRequest = new ServerRequest(
            $this->method(),
            $this->uri(),
            $this->headers,
            $this->body,
            $this->protocolVersion(),
            $this->serverParameters
        );
        return $psrRequest->withParsedBody($this->parameters)
                          ->withCookieParams($this->cookies)
                          ->withQueryParams($this->queryParameters)
                          ->withUploadedFiles($this->files);
    }

    public function getEnvironmentName(): string
    {
        return $this->serverParameters['SERVER_NAME'];
    }

    public function method(): string
    {
        return $this->serverParameters['REQUEST_METHOD'] ?? 'GET';
    }

    public function header(string $name, $defaultValue = null)
    {
        return $this->headers[$name] ?? $defaultValue;
    }

    public function languageCodes(): array
    {
        $languageHeader = $this->header('Accept-Language');
        return $this->parseHeader($languageHeader) ?? [];
    }

    public function parameters()
    {
        return $this->queryParameters + $this->parameters;
    }

    public function parameter(string $name, $defaultValue = null)
    {
        return $this->parameters[$name] ?? $this->queryParameters[$name] ?? $defaultValue;
    }

    public function hasParameter(string $name)
    {
        return isset($this->parameters[$name]) || isset($this->queryParameters[$name]);
    }

    public function sessionParameter(string $name, $defaultValue = null)
    {
        $generator = $this->sessionParameters;
        if ($generator) {
            $parameters = $generator();
            return $parameters[$name] ?? $defaultValue;
        }
        return $defaultValue;
    }

    public function hasSessionParameter(string $name)
    {
        $generator = $this->sessionParameters;
        if ($generator) {
            $parameters = $generator();
            return isset($parameters[$name]);
        }
        return false;
    }

    public function cookie(string $name, $defaultValue = null)
    {
        return $this->cookies[$name] ?? $defaultValue;
    }

    public function payload(): string
    {
        $this->body->rewind();
        return $this->body->getContents();
    }

    public function hasCookie(string $name)
    {
        return isset($this->cookies[$name]);
    }

    public function environmentNameEndsWith(string $suffix): bool
    {
        return $suffix == "" || substr($this->getEnvironmentName(), -strlen($suffix)) === $suffix;
    }

    public function uri(): string
    {
        return $this->serverParameters['REQUEST_URI'] ?? '';
    }

    public function path(): string
    {
        if (!$this->path) {
            $position = strpos($this->uri(), '?');
            $this->path = $position ? substr($this->uri(), 0, $position) : $this->uri();
        }
        return $this->path;
    }

    public function protocolVersion(): string
    {
        return isset($this->serverParameters['SERVER_PROTOCOL'])
            ? str_replace('HTTP/', '', $this->serverParameters['SERVER_PROTOCOL'])
            : '1.1';
    }

    /**
     * Compare path tokens side by side. Returns false if no match, true if match without capture,
     * and array with matched tokens if used with capturing pattern
     *
     * @param array $pathTokens
     * @param array $patternTokens
     *
     * @return array|bool
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
        return count($matches) == 0 ? true : $matches;
    }

    /**
     * Parse header
     *
     * @param string $headerString
     *
     * @return string[]
     */
    protected function parseHeader(string $headerString): array
    {
        $ranges = explode(',', trim(strtolower($headerString)));
        foreach ($ranges as $i => $range) {
            $tokens = explode(';', trim($range), 2);
            $type = trim(array_shift($tokens));
            $priority = 1000 - $i;
            foreach ($tokens as $token) {
                if (($position = strpos($token, '=')) !== false) {
                    $key = substr($token, 0, $position);
                    $value = substr($token, $position + 1);
                    if (trim($key) == 'q') {
                        $priority = 1000 * $value - $i;
                        break;
                    }
                }
            }
            $result[$type] = $priority;
        }
        arsort($result);
        return array_keys($result);
    }

    public function pathMatches(string $path): bool
    {
        return $this->path() == $path;
    }

    public function pathMatchesPattern(string $pattern)
    {
        $pathTokens = explode('/', $this->path());
        $patternTokens = explode('/', $pattern);
        if (count($pathTokens) != count($patternTokens)) {
            return false;
        }
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function pathStartsWith(string $prefix): bool
    {
        $length = strlen($prefix);
        return substr($this->path(), 0, $length) == $prefix;
    }

    public function pathStartsWithPattern(string $pattern)
    {
        $pathTokens = explode('/', $this->path());
        $patternTokens = explode('/', $pattern);
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function preconditionFulfilled(Entity $entity, CacheItemPoolInterface $cache): bool
    {
        $matchHeader           = $this->header('If-Match');
        $modifiedSinceHeader   = $this->header('If-Modified-Since');
        $noneMatchHeader       = $this->header('If-None-Match');
        $unmodifiedSinceHeader = $this->header('If-Unmodified-Since');

        if (is_null($matchHeader) &&
            is_null($modifiedSinceHeader) &&
            is_null($noneMatchHeader) &&
            is_null($unmodifiedSinceHeader)) {
            return true;
        }
        $cacheEntry   = $entity->load($cache);
        $tag          = $cacheEntry['tag'];
        $lastModified = $cacheEntry['modified'];
        if (!is_null($matchHeader) && $tag == $matchHeader) {
            return true;
        }
        if (!is_null($modifiedSinceHeader) && $lastModified > strtotime($modifiedSinceHeader)) {
            return true;
        }
        if (!is_null($noneMatchHeader) && $tag != $noneMatchHeader) {
            return true;
        }
        if (!is_null($unmodifiedSinceHeader) && $lastModified < strtotime($unmodifiedSinceHeader)) {
            return true;
        }
        return false;
    }

    public function getDate(): int
    {
        $dateHeader = $this->header('Date');
        if (empty($dateHeader)) {
            return -1;
        }
        $format = 'D M d Y H:i:s O+';
        $dateTime = DateTime::createFromFormat($format, $dateHeader[0]);
        return $dateTime->getTimestamp();
    }

    private static function readHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
        $headers = [];
        $aliases = [
            'CONTENT_TYPE'                => 'Content-Type',
            'CONTENT_LENGTH'              => 'Content-Length',
            'CONTENT_MD5'                 => 'Content-MD5',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Authorization',
            'PHP_AUTH_DIGEST'             => 'Authorization',
        ];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == "HTTP_") {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            } elseif (isset($aliases[$name]) and !isset($headers[$aliases[$name]])) {
                $headers[$aliases[$name]] = $value;
            }
        }
        if (!isset($headers['Authorization']) and isset($_SERVER['PHP_AUTH_USER'])) {
            $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
            $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $password);
        }
        return $headers;
    }
}
