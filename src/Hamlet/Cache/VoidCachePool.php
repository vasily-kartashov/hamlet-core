<?php

namespace Hamlet\Cache;

use Cache\Adapter\Common\CacheItem;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class VoidCachePool implements CacheItemPoolInterface
{
    /**
     * @param string $key
     * @return CacheItemInterface
     */
    public function getItem($key): CacheItemInterface
    {
        return new CacheItem($key, false, null);
    }

    /**
     * @param string[] $keys
     * @return CacheItemInterface[]
     */
    public function getItems(array $keys = []): array
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = new CacheItem($key, false, null);
        }
        return $items;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasItem($key): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function deleteItem($key): bool
    {
        return true;
    }

    /**
     * @param string[] $keys
     * @return bool
     */
    public function deleteItems(array $keys): bool
    {
        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    public function save(CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return true;
    }
}
