<?php

namespace Hamlet\Entities;

use Psr\Cache\CacheItemPoolInterface;

interface Entity
{
    /**
     * Get caching time in seconds. Default caching time is 0
     */
    public function getCachingTime(): int;

    /**
     * Get string representation of the entity
     */
    public function getContent(): string;

    /**
     * Get content language
     * @return string|null
     */
    public function getContentLanguage();

    /**
     * Get cache key of the entity
     */
    public function getKey(): string;

    /**
     * Get media type
     * @return string|null
     */
    public function getMediaType();

    /**
     * Load entity from cache or generate it
     * @param CacheItemPoolInterface $cache
     * @return CacheValue
     */
    public function load(CacheItemPoolInterface $cache): CacheValue;
}
