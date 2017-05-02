<?php

namespace Hamlet\Responses;

use GuzzleHttp\Psr7\BufferStream;
use Hamlet\Cache\Cache;
use Hamlet\Entities\Entity;
use Hamlet\Requests\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Responses classes should be treated as immutable although they are clearly not. The current design makes it
 * developer's responsibility to make sure that the response objects are always well-formed.
 */
class Response implements ResponseInterface
{
    /** @var string */
    protected $protocolVersion = '1.1';

    /** @var int */
    protected $statusCode = 0;

    /** @var string */
    protected $reasonPhrase = '';

    /** @var string[][] */
    protected $headers = [];

    /** @var Entity */
    protected $entity = null;

    /** @var StreamInterface */
    protected $body = null;

    /** @var bool */
    protected $embedEntity = true;

    /**
     * @var array {
     *      string $name
     *      string $value
     *      string $path
     *      int $timeToLive
     * }
     */
    protected $cookies = [];

    /** @var array */
    protected $session = [];

    protected function __construct(int $statusCode = 0) {
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    protected function setStatus(int $statusCode, string $reasonPhrase = '')
    {
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
    }

    public function output(Request $request, Cache $cache)
    {
        if (count($this->session) > 0) {
            if (!session_id()) {
                session_start();
            }
            foreach ($this->session as $name => $value) {
                $_SESSION[$name] = $value;
            }
        }

        header($this->getStatusLine());
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value);
            }
        }

        foreach ($this->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], time() + $cookie['timeToLive'], $cookie['path']);
        }

        if ($this->entity) {
            $cacheEntry = $this->entity->load($cache);
            $now = time();
            $maxAge = max(0, $cacheEntry['expires'] - $now);

            header('ETag: ' . $cacheEntry['tag']);
            header('Last-Modified: ' . $this->formatTimestamp($cacheEntry['modified']));
            header('Cache-Control: public, max-age=' . $maxAge);
            header('Expires: ' . $this->formatTimestamp($now + $maxAge));

            if ($this->embedEntity) {
                header('Content-Type: ' . $this->entity->getMediaType());
                header('Content-Length: ' . $cacheEntry['length']);
                header('Content-MD5: ' . $cacheEntry['digest']);
                $language = $this->entity->getContentLanguage();
                if ($language) {
                    header('Content-Language: ' . $language);
                }
                echo $cacheEntry['content'];
            }
        } elseif ($this->body) {
            $payload = $this->body->getContents();
            header('Content-Length: ' . strlen($payload));
            echo $payload;
        }

        exit;
    }

    protected function formatTimestamp(int $timestamp): string
    {
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    public function setHeader(string $name, string $value)
    {
        $this->headers[$name][] = $value;
    }

    public function setCookie(string $name, string $value, string $path, int $timeToLive)
    {
        $this->cookies[] = [
            'name'       => $name,
            'value'      => $value,
            'path'       => $path,
            'timeToLive' => $timeToLive,
        ];
    }

    public function setSessionParameter(string $name, $value)
    {
        $this->session[$name] = $value;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    protected function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    protected function setEmbedEntity(bool $embedEntity)
    {
        $this->embedEntity = $embedEntity;
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        $copy = clone $this;
        $copy->setProtocolVersion($version);
        return $copy;
    }

    protected function setProtocolVersion($version)
    {
        $this->protocolVersion = $version;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    public function getHeader($name)
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine($name)
    {
        $values = $this->headers[$name] ?? [];
        return join(', ', $values);
    }

    public function withHeader($name, $value)
    {
        $copy = clone $this;
        $headers = $this->headers;
        $headers[$name] = is_array($value) ? $value : [$value];
        $copy->setHeaders($headers);
        return $copy;
    }

    protected function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function withAddedHeader($name, $value)
    {
        $headers = $this->headers;
        if (!isset($header[$name])) {
            $headers[$name] = [];
        }
        $headers[$name] += is_array($value) ? $value : [$value];
        $copy = clone $this;
        $copy->setHeaders($headers);
        return $copy;
    }

    public function withoutHeader($name)
    {
        $headers = $this->headers;
        unset($headers[$name]);
        $copy = clone $this;
        $copy->setHeaders($headers);
        return $copy;
    }

    public function getBody()
    {
        if (!isset($this->body)) {
            $this->body = new BufferStream();
        }
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $copy = clone $this;
        $copy->setBody($body);
        return $copy;
    }

    protected function setBody(StreamInterface $body)
    {
        $this->body = $body;
    }

    public function withStatus($statusCode, $reasonPhrase = '')
    {
        $copy = clone $this;
        $copy->setStatus($statusCode, $reasonPhrase);
        return $copy;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    private function getStatusLine()
    {
        static $phrases = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-status',
            208 => 'Already Reported',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Large',
            415 => 'Unsupported Media Type',
            416 => 'Requested range not satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            511 => 'Network Authentication Required',
        ];
        $reasonPhrase = $this->reasonPhrase ?? $phrases[$this->statusCode] ?? '';
        return 'HTTP/' . $this->protocolVersion . ' ' . $this->statusCode . ' ' . $reasonPhrase;
    }
}
