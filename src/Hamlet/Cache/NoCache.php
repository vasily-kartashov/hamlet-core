<?php

namespace Hamlet\Cache;

class NoCache implements CacheInterface
{
    public function get($key, $defaultValue = null)
    {
        return [$defaultValue, false];
    }

    public function set($key, $value, $timeToLive = 0)
    {
    }
}