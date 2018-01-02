<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;
use Hamlet\Entities\StreamEntity;
use Hamlet\Requests\Request;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Responses classes should be treated as immutable although they are clearly not. The current design makes it
 * developer's responsibility to make sure that the response objects are always well-formed.
 */
class Response
{
    /** @var int */
    protected $statusCode = 0;

    /** @var string[] */
    protected $headers = [];

    /** @var Entity|null */
    protected $entity;

    /** @var bool */
    protected $embedEntity = true;

    /** @var Cookie[]  */
    protected $cookies = [];

    /** @var string[] */
    protected $session = [];

    /**
     * @param int $statusCode
     * @param Entity|null $entity
     * @param bool $embedEntity
     * @param string[] $headers
     * @param Cookie[] $cookies
     * @param string[] $session
     */
    protected function __construct(
        int $statusCode = 0,
        $entity = null,
        $embedEntity = true,
        array $headers = [],
        array $cookies = [],
        array $session = []
    ) {
        $this->statusCode      = $statusCode;
        $this->entity          = $entity;
        $this->embedEntity     = $embedEntity;
        $this->headers         = $headers;
        $this->cookies         = $cookies;
        $this->session         = $session;
    }

    public static function fromPsrResponse(ResponseInterface $response): Response
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = join(', ', $values);
        }
        $entity = new StreamEntity($response->getBody());

        return new Response(
            $response->getStatusCode(),
            $entity,
            true,
            $headers
        );
    }

    /**
     * @param Request $request
     * @param CacheItemPoolInterface $cache
     * @return void
     */
    public function output(/** @noinspection PhpUnusedParameterInspection */ Request $request, CacheItemPoolInterface $cache)
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
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            setcookie($cookie->name(), $cookie->value(), time() + $cookie->timeToLive(), $cookie->path());
        }

        if ($this->entity) {
            $cacheValue = $this->entity->load($cache);
            $now = time();
            $maxAge = max(0, $cacheValue->expiry() - $now);

            header('ETag: ' . $cacheValue->tag());
            header('Last-Modified: ' . $this->formatTimestamp($cacheValue->modified()));
            header('Cache-Control: public, max-age=' . $maxAge);
            header('Expires: ' . $this->formatTimestamp($now + $maxAge));

            if ($this->embedEntity) {
                header('Content-Length: ' . $cacheValue->length());
                header('Content-MD5: ' . $cacheValue->digest());
                $language = $this->entity->getContentLanguage();
                if ($language) {
                    header('Content-Language: ' . $language);
                }
                $mediaType = $this->entity->getMediaType();
                if ($mediaType) {
                    header('Content-Type: ' . $mediaType);
                }
                echo $cacheValue->content();
            }
        }
        exit;
    }

    protected function formatTimestamp(int $timestamp): string
    {
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    protected function withCookie(Cookie $cookie): Response
    {
        $this->cookies[] = $cookie;
        return $this;
    }

    protected function withHeader(string $name, string $value): Response
    {
        $this->headers[$name] = $value;
        return $this;
    }

    protected function withSessionParameter(string $name, string $value): Response
    {
        $this->session[$name] = $value;
        return $this;
    }

    protected function withEntity(Entity $entity): Response
    {
        $this->entity = $entity;
        return $this;
    }

    protected function withStatusCode(int $code): Response
    {
        $this->statusCode = $code;
        return $this;
    }

    protected function withEmbedEntity(bool $embed): Response
    {
        $this->embedEntity = $embed;
        return $this;
    }

    private function getStatusLine(): string
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
        $reasonPhrase = $phrases[$this->statusCode] ?? '';
        return 'HTTP/1.1 ' . $this->statusCode . ' ' . $reasonPhrase;
    }
}
