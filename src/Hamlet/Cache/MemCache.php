<?php

namespace Hamlet\Cache;

use Memcached;

class MemCache implements CacheInterface
{
    /** @var array {
     *      string $host
     *      int $port
     * }
     */
    protected $endpoints;

    /**
     * @param $endpoints array {
     *      string $host
     *      int $port
     * }
     */
    public function __construct(array $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    /**
     * Get memcached client
     *
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

    /**
     * Get value stored in cache
     *
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return array
     */
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

    /**
     * Store value in cache
     *
     * @param string $key
     * @param mixed $value
     *
     * @param int $timeToLive
     */
    public function set($key, $value, $timeToLive = 0)
    {
        $client = $this->getClient();
        $client->set($key, $value, $timeToLive);
    }
}