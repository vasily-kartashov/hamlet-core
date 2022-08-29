<?php

namespace Hamlet\Entities;

use Hamlet\Cache\CacheValue;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use function Hamlet\Cast\_class;

abstract class AbstractEntity implements Entity
{
    /**
     * @var CacheValue|null
     */
    private $cacheValue = null;

    public function getContentLanguage(): ?string
    {
        return null;
    }

    public function load(CacheItemPoolInterface $cache): CacheValue
    {
        if ($this->cacheValue !== null) {
            return $this->cacheValue;
        }
        $key = $this->getKey();
        try {
            $cacheItem = $cache->getItem($key);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        if ($cacheItem->isHit()) {
            $this->cacheValue = _class(CacheValue::class)->assert($cacheItem->get());
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
