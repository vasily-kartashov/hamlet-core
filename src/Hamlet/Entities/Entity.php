<?php

namespace Hamlet\Entities;

use Hamlet\Cache\CacheValue;
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
     */
    public function getContentLanguage(): ?string;

    /**
     * Get cache key of the entity
     */
    public function getKey(): string;

    /**
     * Get media type
     */
    public function getMediaType(): ?string;

    /**
     * Load entity from cache or generate it
     */
    public function load(CacheItemPoolInterface $cache): CacheValue;
}
