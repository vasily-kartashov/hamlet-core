<?php

namespace Hamlet\Responses;

use Hamlet\Entities\Entity;
use Hamlet\Entities\StreamEntity;
use Hamlet\Requests\Request;
use Hamlet\Writers\ResponseWriter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use function Hamlet\Cast\_string;

/**
 * Responses classes should be treated as immutable although they are clearly not. The current design makes it
 * developer's responsibility to make sure that the response objects are always well-formed.
 */
class Response
{
    /**
     * @var array<int,string>
     */
    private const PHRASES = [
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

    /**
     * @var int
     */
    protected $statusCode = 0;

    /**
     * @var array<string,string>
     */
    protected $headers = [];

    /**
     * @var Entity|null
     */
    protected $entity;

    /**
     * @var bool
     */
    protected $embedEntity = true;

    /**
     * @var array<Cookie>
     */
    protected $cookies = [];

    /**
     * @var array<string,mixed>
     */
    protected $sessionParams = [];

    /**
     * @param int $statusCode
     * @param Entity|null $entity
     * @param bool $embedEntity
     * @param array<string,string> $headers
     * @param array<Cookie> $cookies
     * @param array<string,mixed> $session
     */
    protected function __construct(
        int $statusCode = 0,
        Entity $entity = null,
        bool $embedEntity = true,
        array $headers = [],
        array $cookies = [],
        array $session = []
    ) {
        $this->statusCode    = $statusCode;
        $this->entity        = $entity;
        $this->embedEntity   = $embedEntity;
        $this->headers       = $headers;
        $this->cookies       = $cookies;
        $this->sessionParams = $session;
    }

    public static function fromResponseInterface(ResponseInterface $response): Response
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            assert(is_string($name));
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

    public function output(Request $request, CacheItemPoolInterface $cache, ResponseWriter $writer): void
    {
        $writer->status($this->statusCode, $this->getStatusLine());

        if ($request->sessionStarted() || !empty($this->sessionParams)) {
            $writer->session($request, $this->sessionParams);
        }

        foreach ($this->headers as $name => $value) {
            $writer->header($name, $value);
        }

        foreach ($this->cookies as $cookie) {
            $writer->cookie($cookie->name(), $cookie->value(), time() + $cookie->timeToLive(), $cookie->path());
        }

        if ($this->entity) {
            $cacheValue = $this->entity->load($cache);
            $now = time();
            $maxAge = max(0, $cacheValue->expiry() - $now);

            $writer->header('ETag', $cacheValue->tag());
            $writer->header('Last-Modified', $this->formatTimestamp($cacheValue->modified()));
            $writer->header('Cache-Control', 'public, max-age=' . $maxAge);
            $writer->header('Expires', $this->formatTimestamp($now + $maxAge));

            if ($this->embedEntity) {
                $writer->header('Content-Length', (string) $cacheValue->length());
                $writer->header('Content-MD5', $cacheValue->digest());
                $language = $this->entity->getContentLanguage();
                if ($language) {
                    $writer->header('Content-Language', $language);
                }
                $mediaType = $this->entity->getMediaType();
                if ($mediaType) {
                    $writer->header('Content-Type', $mediaType);
                }
                $writer->writeAndEnd(_string()->cast($cacheValue->content()));
            } else {
                $writer->end();
            }
        } else {
            $writer->end();
        }
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

    protected function getStatusLine(): string
    {
        $reasonPhrase = self::PHRASES[$this->statusCode] ?? '';
        return 'HTTP/1.1 ' . $this->statusCode . ' ' . $reasonPhrase;
    }
}
