<?php
namespace Hamlet\Request;

use Hamlet\Cache\CacheInterface;
use Hamlet\Entity\EntityInterface;
use \JsonSerializable;
interface RequestInterface extends JsonSerializable
{
    /**
     * @param string $suffix
     * @return bool
     */
    public function environmentNameEndsWith($suffix);

    /**
     * @param string $name
     * @param string $defaultValue
     * @return string|null
     */
    public function getCookie($name, $defaultValue = null);

    /**
     * @return string
     */
    public function getEnvironmentName();

    /**
     * @param string $headerName
     * @return string
     */
    public function getHeader($headerName);

    /**
     * @return string[]
     */
    public function getLanguageCodes();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @param string $name
     * @param string $defaultValue
     * @return string|null
     */
    public function getParameter($name, $defaultValue = null);

    /**
     * @return string
     */
    public function getBody();

    /**
     * @return string[]
     */
    public function getParameters();

    /**
     * @return string
     */
    public function getRemoteIpAddress();

    /**
     * @param string $name
     * @param string $defaultValue
     * @return string|null
     */
    public function getSessionParameter($name, $defaultValue = null);

    /**
     * @return string[]
     */
    public function getSessionParameters();

    /**
     * @param string $name
     * @return bool
     */
    public function hasParameter($name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasSessionParameter($name);

    /**
     * Check if the request path matches exactly provided string
     *
     * @param string $path
     *
     * @return bool
     */
    public function pathMatches($path);

    /**
     * Check if the request path matches the pattern
     *
     * @param string $pattern
     *
     * @return string[]|bool
     */
    public function pathMatchesPattern($pattern);

    /**
     * Check if the request path starts with path provided
     *
     * @param string $prefix
     *
     * @return bool
     */
    public function pathStartsWith($prefix);

    /**
     * Check if the request path starts with pattern
     *
     * @param string $pattern
     *
     * @return string[]|bool
     */
    public function pathStartsWithPattern($pattern);

    /**
     * Check if the request fulfills the preconditions for conditional request
     *
     * @param \Hamlet\Entity\EntityInterface $entity
     * @param \Hamlet\Cache\CacheInterface $cache
     *
     * @return bool
     */
    public function preconditionFulfilled(EntityInterface $entity, CacheInterface $cache);

}