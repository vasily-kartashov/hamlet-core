<?php

namespace Hamlet\Entities;

use Hamlet\Cache\CacheValue;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;

abstract class AbstractEntity implements Entity
{
    /** @var CacheValue|null */
    private $cacheValue = null;

    public function getContentLanguage()
    {
        return null;
    }

    /**
     * @param CacheItemPoolInterface $cache
     * @return CacheValue
     * @psalm-suppress InvalidCatch
     */
    public function load(CacheItemPoolInterface $cache): CacheValue
    {
        if ($this->cacheValue !== null) {
            return $this->cacheValue;
        }
        $key = $this->getKey();
        try {
            $cacheItem = $cache->getItem($key);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode());
        }

        if ($cacheItem->isHit()) {
            $this->cacheValue = $cacheItem->get();
        }

        $now = time();
        if (!$cacheItem->isHit() || ($this->cacheValue && $this->cacheValue->expiry() <= $now)) {
            $content   = $this->getContent();
            $tag       = md5($content);
            $newExpiry = $now + $this->getCachingTime();
            if ($this->cacheValue && $this->cacheValue->tag() == $tag) {
                $this->cacheValue = $this->cacheValue->extendExpiry($newExpiry);
            } else {
                $this->cacheValue = new CacheValue($content, $now, $newExpiry);
            }
            $cacheItem->set($this->cacheValue);
            $cache->save($cacheItem);
        }

        assert($this->cacheValue !== null);
        return $this->cacheValue;
    }

    public function getCachingTime(): int
    {
        return 0;
    }
}
