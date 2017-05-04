<?php

namespace Hamlet\Entities;

class CacheValue
{
    private $content;
    private $tag;
    private $digest;
    private $length;
    private $modified;
    private $expiry;

    public function __construct($content, int $modified, int $expiry)
    {
        $this->content  = $content;
        $this->tag      = md5($content);
        $this->digest   = base64_encode(pack('H*', $this->tag));
        $this->length   = strlen($content);
        $this->modified = $modified;
        $this->expiry   = $expiry;
    }

    public function extendExpiry(int $expires)
    {
        return new CacheValue($this->content, $this->modified, $expires);
    }

    public function content()
    {
        return $this->content;
    }

    public function tag()
    {
        return $this->tag;
    }

    public function digest()
    {
        return $this->digest;
    }

    public function length()
    {
        return $this->length;
    }

    public function modified()
    {
        return $this->modified;
    }

    public function expiry()
    {
        return $this->expiry;
    }
}
