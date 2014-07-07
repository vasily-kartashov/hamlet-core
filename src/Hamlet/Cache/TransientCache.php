<?php

namespace Hamlet\Cache;

class TransientCache implements CacheInterface
{
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
}