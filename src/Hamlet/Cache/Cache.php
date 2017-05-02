<?php

namespace Hamlet\Cache;

interface Cache
{
    public function get(string $key, $defaultValue = null);

    public function set(string $key, $value, int $timeToLive = 0);

    public function delete(string... $keys);
}
