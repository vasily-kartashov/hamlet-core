<?php

namespace Hamlet\Cache;

class CacheValue
{
    /** @var mixed */
    private $content;

    /** @var string */
    private $tag;

    /** @var string */
    private $digest;

    /** @var int */
    private $length;

    /** @var int */
    private $modified;

    /** @var int */
    private $expiry;

    /**
     * @param mixed $content
     * @param int $modified
     * @param int $expiry
     */
    public function __construct($content, int $modified, int $expiry)
    {
        $contentAsString = (string) $content;
        $md5 = md5($contentAsString);

        $this->content  = $content;
        $this->tag      = '"' . $md5 . '"';
        $this->digest   = base64_encode(pack('H*', $md5));
        $this->length   = strlen($contentAsString);
        $this->modified = $modified;
        $this->expiry   = $expiry;
    }

    public function extendExpiry(int $expires): CacheValue
    {
        return new CacheValue($this->content, $this->modified, $expires);
    }

    /**
     * @return mixed
     */
    public function content()
    {
        return $this->content;
    }

    public function tag(): string
    {
        return $this->tag;
    }

    public function digest(): string
    {
        return $this->digest;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function modified(): int
    {
        return $this->modified;
    }

    public function expiry(): int
    {
        return $this->expiry;
    }
}
