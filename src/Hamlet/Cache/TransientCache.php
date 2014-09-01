<?php

namespace Hamlet\Cache;

class TransientCache implements CacheInterface
{
    /**
     * @var { mixed $value, int $expiry }[]
     */
    private $entries = [];

    public function get($key, $defaultValue = null)
    {
        if (isset($this->entries[$key])) {
            $entry = $this->entries[$key];
            if ($entry['expiry'] > time() || $entry['expiry'] == -1) {
                return [$entry['value'], true];
            }
        }
        return [$defaultValue, false];
    }

    public function set($key, $value, $timeToLive = 0)
    {
        if ($timeToLive == 0) {
            $expiry = -1;
        } else {
            $expiry = time() + $timeToLive;
        }
        $this->entries[$key] = [
            'value' => $value,
            'expiry' => $expiry,
        ];
    }

    /**
     * Delete an item from the cache
     * @param string $key
     */
    public function delete($key)
    {
        if (isset($this->entries[$key])) {
           unset($this->entries[$key]);
        }
    }

    /**
     * Delete multiple items from the cache
     * @param string[] $keys
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }
}