<?php

namespace Hamlet\Cache;

use Memcached;

class MemCache implements CacheInterface
{
    /** @var string */
    protected $endpoint;

    /** @var int */
    protected $port;

    /** @var \Memcached */
    protected $client = null;

    public function __construct($endpoint, $port = 11211)
    {
        $this->endpoint = $endpoint;
        $this->port = $port;
    }

    public function get($key, $defaultValue = null)
    {
        if ($this->client == null) {
            $this->client = new Memcached();
            $this->client->addServer($this->endpoint, $this->port);
        }
        $found = true;
        $value = $this->client->get($key);
        if ($this->client->getResultCode() == Memcached::RES_NOTFOUND) {
            $found = false;
            $value = $defaultValue;
        }
        return [$value, $found];
    }

    public function set($key, $value, $timeToLive = 0)
    {
        if ($this->client == null) {
            $this->client = new Memcached();
            $this->client->addServer($this->endpoint, $this->port);
        }
        $this->client->set($key, $value, $timeToLive);
    }
}