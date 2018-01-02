<?php

namespace Hamlet\Entities;

use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractEntity implements Entity
{
    /** @var CacheValue */
    private $cacheValue = null;

    public function getContentLanguage()
    {
        return null;
    }

    /**
     * @param CacheItemPoolInterface $cache
     * @return CacheValue
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function load(CacheItemPoolInterface $cache): CacheValue
    {
        if ($this->cacheValue) {
            return $this->cacheValue;
        }
        $key = $this->getKey();
        $cacheItem = $cache->getItem($key);

        if ($cacheItem->isHit()) {
            $this->cacheValue = $cacheItem->get();
        }

        $now = time();
        if (!$cacheItem->isHit() || $now >= $this->cacheValue->expiry()) {
            $content   = $this->getContent();
            $tag       = md5($content);
            $newExpiry = $now + $this->getCachingTime();
            if ($this->cacheValue && $tag == $this->cacheValue->tag()) {
                $this->cacheValue = $this->cacheValue->extendExpiry($newExpiry);
            } else {
                $this->cacheValue = new CacheValue($content, $now, $newExpiry);
            }
            $cacheItem->set($this->cacheValue);
            $cache->save($cacheItem);
        }
        return $this->cacheValue;
    }

    public function getCachingTime(): int
    {
        return 0;
    }
}
