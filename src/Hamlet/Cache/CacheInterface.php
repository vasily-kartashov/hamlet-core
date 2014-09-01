<?php
namespace Hamlet\Cache;

interface CacheInterface
{
    /**
     * Get object from cache. If not available return default value
     * @param string $key
     * @param mixed $defaultValue
     * @return { mixed $value, bool $found }
     */
    public function get($key, $defaultValue = null);

    /**
     * Set cache value. If time to live is 0 keeps the value indefinitely
     * @param string $key
     * @param mixed $value
     * @param int $timeToLive
     */
    public function set($key, $value, $timeToLive = 0);

    /**
     * Delete an item from the cache
     * @param string $key
     */
    public function delete($key);

    /**
     * Delete multiple items from the cache
     * @param string[] $keys
     */
    public function deleteMultiple($keys);

}