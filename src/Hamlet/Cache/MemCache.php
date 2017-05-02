<?php

namespace Hamlet\Cache;

use Memcached;

class MemCache implements Cache
{
    protected $endpoints;

    public function __construct(array $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    private function getClient(): Memcached
    {
        static $client = null;
        if ($client == null) {
            $client = new Memcached();
            foreach ($this->endpoints as $endpoint) {
                $client->addServer($endpoint['host'], $endpoint['port']);
            }
        }
        return $client;
    }

    public function get(string $key, $defaultValue = null)
    {
        $client = $this->getClient();
        $found = true;
        $value = $client->get($key);
        if ($client->getResultCode() == Memcached::RES_NOTFOUND) {
            $found = false;
            $value = $defaultValue;
        }
        return [$value, $found];
    }

    public function set(string $key, $value, int $timeToLive = 0)
    {
        $this->getClient()->set($key, $value, $timeToLive);
    }

    public function delete(string... $keys)
    {
        $this->getClient()->deleteMulti($keys);
    }
}
