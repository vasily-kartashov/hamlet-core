<?php

namespace Hamlet\Response;

use Hamlet\Cache\CacheInterface;
use Hamlet\Entity\EntityInterface;
use Hamlet\Request\RequestInterface;

/**
 * Response classes should be treated as immutable although they are clearly not. The current design makes it
 * developer's responsibility to make sure that the response objects are always well-formed.
 */
class AbstractResponse implements ResponseInterface
{
    /** @var string */
    protected $status;

    /** @var string[] */
    protected $headers = array();

    /** @var \Hamlet\Entity\EntityInterface */
    protected $entity = null;

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
    protected $cookies = array();

    /** @var array */
    protected $session = array();

    /**
     * Constructor
     * @param string $status
     */
    protected function __construct($status = '')
    {
        $this->status = (string) $status;
    }

    /**
     * Get current status code and description
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set current status and description
     * @param $status
     */
    protected function setStatus($status)
    {
        $this->status = (string) $status;
    }

    /**
     * Set response entity
     * @param \Hamlet\Entity\EntityInterface $entity
     */
    protected function setEntity(EntityInterface $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Set if the entity should be embedded into the response
     * @param bool $embedEntity
     */
    protected function setEmbedEntity($embedEntity)
    {
        $this->embedEntity = (bool) $embedEntity;
    }

    /**
     * Output entity
     * @param \Hamlet\Request\RequestInterface $request
     * @param \Hamlet\Cache\CacheInterface $cache
     */
    public function output(RequestInterface $request, CacheInterface $cache)
    {

        if (count($this->session) > 0) {
            if (!session_id()) {
                session_start();
            }
            foreach ($this->session as $name => $value) {
                $_SESSION[$name] = $value;
            }
        }

        header('HTTP/1.1 ' . $this->status);
        foreach ($this->headers as $name => $content) {
            header($name . ': ' . $content);
        }

        foreach ($this->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], time() + $cookie['timeToLive'], $cookie['path']);
        }

        if (!is_null($this->entity)) {
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
        }

        exit;
    }

    /**
     * Set additional header
     * @param string $headerName
     * @param string $headerValue
     */
    public function setHeader($headerName, $headerValue)
    {
        $this->headers[(string) $headerName] = (string) $headerValue;
    }

    /**
     * Add cookie to response
     * @param string $name
     * @param string $value
     * @param string $path
     * @param int $timeToLive
     */
    public function setCookie($name, $value, $path, $timeToLive)
    {
        $this->cookies[] = array(
            'name' => (string) $name,
            'value' => (string) $value,
            'path' => (string) $path,
            'timeToLive' => (int) $timeToLive,
        );
    }

    /**
     * Set session parameter
     * @param string $name
     * @param string $value
     */
    public function setSessionParameter($name, $value)
    {
        $this->session[(string) $name] = $value;
    }

    /**
     * Convert timestamp into RFC 822 format
     * @param $timestamp
     * @return string
     */
    protected function formatTimestamp($timestamp)
    {
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }

    /**         *
     * @return \Hamlet\Entity\EntityInterface|null
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
