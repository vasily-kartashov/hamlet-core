<?php
namespace Hamlet\Cache;

interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return array
     */
    public function get($key, $defaultValue = null);

    /**
     * @param string $key
     * @param mixed $value
     * @param int $timeToLive
     */
    public function set($key, $value, $timeToLive = 0);
}