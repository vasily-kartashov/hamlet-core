<?php

namespace Hamlet\Requests;

use DateTime;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;
use Hamlet\Cache\Cache;
use Hamlet\Entities\Entity;

class Request extends ServerRequest
{
    public static function fromGlobals()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $uri = self::getUriFromGlobals();
        $body = new LazyOpenStream('php://input', 'r+');
        $protocol = isset($_SERVER['SERVER_PROTOCOL'])
            ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL'])
            : '1.1';

        $serverRequest = new Request($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    public function getEnvironmentName(): string
    {
        return $this->getServerParams()['SERVER_NAME'];
    }

    public function environmentNameEndsWith(string $suffix): bool
    {
        return $suffix == "" || substr($this->getEnvironmentName(), -strlen($suffix)) === $suffix;
    }

    public function getLanguageCodes(): array
    {
        $languageHeader = $this->getHeader('Accept-Language');
        return $this->parseHeader($languageHeader) ?? [];
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
        $matches = [];
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
            } else if (urldecode($pathToken) != $patternToken) {
                return false;
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
    protected function parseHeader(string $headerString): array
    {
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

    public function pathMatches(string $path): bool
    {
        return $this->getUri() == $path;
    }

    public function pathMatchesPattern(string $pattern)
    {
        $pathTokens = explode('/', $this->getUri());
        $patternTokens = explode('/', $pattern);
        if (count($pathTokens) != count($patternTokens)) {
            return false;
        }
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function pathStartsWith(string $prefix): bool
    {
        $length = strlen($prefix);
        return substr($this->getUri(), 0, $length) == $prefix;
    }

    public function pathStartsWithPattern(string $pattern)
    {
        $pathTokens = explode('/', $this->getUri());
        $patternTokens = explode('/', $pattern);
        return $this->matchTokens($pathTokens, $patternTokens);
    }

    public function preconditionFulfilled(Entity $entity, Cache $cache): bool
    {
        $matchHeader = $this->getHeader('If-Match');
        $modifiedSinceHeader = $this->getHeader('If-Modified-Since');
        $noneMatchHeader = $this->getHeader('If-None-Match');
        $unmodifiedSinceHeader = $this->getHeader('If-Unmodified-Since');

        if (is_null($matchHeader)     && is_null($modifiedSinceHeader) &&
            is_null($noneMatchHeader) && is_null($unmodifiedSinceHeader)) {
            return true;
        }
        $cacheEntry   = $entity->load($cache);
        $tag          = $cacheEntry['tag'];
        $lastModified = $cacheEntry['modified'];
        if (!is_null($matchHeader) && $tag == $matchHeader) {
            return true;
        }
        if (!is_null($modifiedSinceHeader) && $lastModified > strtotime($modifiedSinceHeader)) {
            return true;
        }
        if (!is_null($noneMatchHeader) && $tag != $noneMatchHeader) {
            return true;
        }
        if (!is_null($unmodifiedSinceHeader) && $lastModified < strtotime($unmodifiedSinceHeader)) {
            return true;
        }

        return false;
    }

    public function getDate(): int
    {
        $dateHeader = $this->getHeader('Date');
        if (empty($dateHeader)) {
            return -1;
        }
        $format = 'D M d Y H:i:s O+';
        $dateTime = DateTime::createFromFormat($format, $dateHeader[0]);
        return $dateTime->getTimestamp();
    }

    public function __toString()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
