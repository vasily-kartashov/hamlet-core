<?php

namespace Hamlet\Cache;

use Memcached;

class MemCache implements CacheInterface
{
    /**
     * @var { string $host, int $port }[]
     */
    protected $endpoints;

    /**
     * @param $endpoints { string $host, int $port }[]
     */
    public function __construct(array $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    /**
     * Get memcached client
     * @return \Memcached
     */
    private function getClient()
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

    public function get($key, $defaultValue = null)
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

    public function set($key, $value, $timeToLive = 0)
    {
        $client = $this->getClient();
        $client->set($key, $value, $timeToLive);
    }
}