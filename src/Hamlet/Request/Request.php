<?php

namespace Hamlet\Request;

use Hamlet\Cache\CacheInterface;
use Hamlet\Entity\EntityInterface;
use \JsonSerializable;
class Request implements RequestInterface
{
    /** @var string[]  */
    protected $cookies;

    /** @var string */
    protected $environmentName;

    /** @var string[] */
    protected $headers;

    /** @var string */
    protected $ip;

    /** @var string */
    protected $method;

    /** @var string */
    protected $path;

    /** @var  string[] */
    protected $parameters;

    /** @var string[] */
    protected $sessionParameters;

    /** @var string */
    protected $body;

    public function __construct($method, $path, $environmentName, $ip, $headers, $parameters, $sessionParameters,
                                $cookies, $body = null)
    {
        assert(is_string($method));
        assert(is_string($path));
        assert(is_string($environmentName));
        assert(is_string($ip));
        assert(is_array($headers));
        assert(is_array($parameters));
        assert(is_array($sessionParameters));
        assert(is_array($cookies));
        if(!is_null($body)){
            assert(is_string($body));
        }

        $this->method = $method;
        $this->path = $path;
        $this->environmentName = $environmentName;
        $this->ip = $ip;
        $this->headers = $headers;
        $this->parameters = $parameters;
        $this->sessionParameters = $sessionParameters;
        $this->cookies = $cookies;
        $this->body = $body;
    }

    public function environmentNameEndsWith($suffix)
    {
        assert(is_string($suffix));
        return $suffix == "" || substr($this->getEnvironmentName(), -strlen($suffix)) === $suffix;
    }

    public function getCookie($name, $defaultValue = null)
    {
        assert(is_string($name));
        if (isset($this->cookies[$name])) {
            return $this->cookies[$name];
        }
        return $defaultValue;
    }

    public function getEnvironmentName()
    {
        return $this->environmentName;
    }

    public function getHeader($name)
    {
        assert(is_string($name));
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

    public function getLanguageCodes()
    {
        return $this->parseHeader($this->getHeader('Accept-Language'));
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getParameter($name, $defaultValue = null)
    {
        assert(is_string($name));
        if (isset($this->parameters[$name])) {
            return urldecode($this->parameters[$name]);
        }
        return $defaultValue;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getSessionParameter($name, $defaultValue = null)
    {
        assert(is_string($name));
        if (isset($this->sessionParameters[$name])) {
            return $this->sessionParameters[$name];
        }
        return $defaultValue;
    }

    public function getSessionParameters()
    {
        return $this->sessionParameters;
    }

    public function hasParameter($name)
    {
        assert(is_string($name));
        return isset($this->parameters[$name]);
    }

    public function hasSessionParameter($name)
    {
        assert(is_string($name));
        return isset($this->sessionParameters[$name]);
    }

    /**
     * Compare path tokens side by side. Returns false if no match, true if match without capture,
     * and array with matched tokens if used with capturing pattern
     *
     * @param array $pathTokens
     * @param array $patternTokens
     *
     * @return array|bool
     */
    protected function matchTokens(array $pathTokens, array $patternTokens)
    {
        $matches = array();
        for ($i = 1; $i < count($patternTokens); $i++) {
            $pathToken = $pathTokens[$i];
            $patternToken = $patternTokens[$i];
            if ($pathToken == '' && $patternToken != '') {
                return false;
            }
            if ($patternToken == '*') {
                continue;
            }
            if (substr($patternToken, 0, 1) == '{') {
                $matches[substr($patternToken, 1, -1)] = urldecode($pathToken);
            } else {
                if (urldecode($pathToken) != $patternToken) {
                    return false;
                }
            }
        }
        return count($matches) == 0 ? true : $matches;
    }

    /**
     * Parse header
     *
     * @param string $headerString
     *
     * @return string[]
     */
    protected function parseHeader($headerString)
    {
        assert(is_string($headerString));
        $ranges = explode(',', trim(strtolower($headerString)));
        foreach ($ranges as $i => $range) {
            $tokens = explode(';', trim($range), 2);
            $type = trim(array_shift($tokens));
            $priority = 1000 - $i;
            foreach ($tokens as $token) {
                if (($position = strpos($token, '=')) !== false) {
                    $key = substr($token, 0, $position);
                    $value = substr($token, $position + 1);
                    if (trim($key) == 'q') {
                        $priority = 1000 * $value - $i;
                        break;
                    }
                }
            }
            $result[$type] = $priority;
        }
        arsort($result);
        return array_keys($result);
    }

    public function pathMatches($path)
    {
        assert(is_string($path));
        return $this->path == (string) $path;
    }

    public function pathMatchesPattern($pattern)
    {
        assert(is_string($pattern));
        $pathTokens = explode('/', $this->path);
        $patternTokens = explode('/', $pattern);
        if (count($pathTokens) != count($patternTokens)) {
            return false;
        }
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function pathStartsWith($prefix)
    {
        assert(is_string($prefix));
        $length = strlen($prefix);
        return substr($this->path, 0, $length) == $prefix;
    }

    public function pathStartsWithPattern($pattern)
    {
        assert(is_string($pattern));
        $pathTokens = explode('/', $this->path);
        $patternTokens = explode('/', $pattern);
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function preconditionFulfilled(EntityInterface $entity, CacheInterface $cache)
    {
        $matchHeader = $this->getHeader('If-Match');
        $modifiedSinceHeader = $this->getHeader('If-Modified-Since');
        $noneMatchHeader = $this->getHeader('If-None-Match');
        $unmodifiedSinceHeader = $this->getHeader('If-Unmodified-Since');

        if (is_null($matchHeader) and is_null($modifiedSinceHeader) and is_null($noneMatchHeader) and is_null($unmodifiedSinceHeader)) {
            return true;
        }

        $cacheEntry = $entity->load($cache);

        $tag = $cacheEntry['tag'];
        $lastModified = $cacheEntry['modified'];

        if (!is_null($matchHeader)) {
            if ($tag == $matchHeader) {
                return true;
            }
        }

        if (!is_null($modifiedSinceHeader)) {
            if ($lastModified > strtotime($modifiedSinceHeader)) {
                return true;
            }
        }

        if (!is_null($noneMatchHeader)) {
            if ($tag != $noneMatchHeader) {
                return true;
            }
        }

        if (!is_null($unmodifiedSinceHeader)) {
            if ($lastModified < strtotime($unmodifiedSinceHeader)) {
                return true;
            }
        }

        return false;
    }

    public function getRemoteIpAddress()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'body' => $this->body,
            'cookies' => $this->cookies,
            'environmentName' => $this->environmentName,
            'headers' => $this->headers,
            'ip' => $this->ip,
            'method' => $this->method,
            'path' => $this->path,
            'parameters' => $this->parameters,
            'sessionParameters' => $this->sessionParameters
        ];
    }
}