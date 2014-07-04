<?php
namespace Hamlet\Entity;

use Hamlet\Cache\CacheInterface;

interface EntityInterface
{
    /**
     * Get caching time in seconds. Default caching time is 0
     * @return int
     */
    public function getCachingTime();

    /**
     * Get string representation of the entity
     * @return string
     */
    public function getContent();

    /**
     * Get content language
     * @return string
     */
    public function getContentLanguage();

    /**
     * Get cache key of the entity
     * @return string
     */
    public function getKey();

    /**
     * Get media type
     * @return string
     */
    public function getMediaType();

    /**
     * Load entity from cache or generate it
     * @param \Hamlet\Cache\CacheInterface $cache
     * @return array {
     *      mixed $content
     *      string $tag
     *      string $digest
     *      int $length
     *      int $modified
     *      int $expires
     * }
     */
    public function load(CacheInterface $cache);
}