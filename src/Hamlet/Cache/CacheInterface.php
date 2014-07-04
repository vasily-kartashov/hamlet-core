<?php
namespace Hamlet\Cache;

interface CacheInterface
{
    /**
     * @param string $key
     * @return array
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value);
}