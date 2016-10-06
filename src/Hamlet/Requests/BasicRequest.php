<?php

namespace Hamlet\Requests {

    use Hamlet\Cache\Cache;
    use Hamlet\Entities\Entity;

    class BasicRequest implements Request {

        /** @var string[] */
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

        /** @var string */
        protected $host;

        public function __construct($method, $path, $environmentName, $ip, $headers, $parameters, $sessionParameters,
                                    $cookies, $host = null, $body = null) {
            $this -> method = $method;
            $this -> path = $path;
            $this -> environmentName = $environmentName;
            $this -> ip = $ip;
            $this -> headers = $headers;
            $this -> parameters = $parameters;
            $this -> sessionParameters = $sessionParameters;
            $this -> cookies = $cookies;
            $this -> body = $body;
            $this -> host = $host;
        }

        public function environmentNameEndsWith(string $suffix) : bool {
            return $suffix == "" || substr($this->getEnvironmentName(), -strlen($suffix)) === $suffix;
        }

        public function getCookie(string $name, $defaultValue = null) : string {
            return $this -> cookies[$name] ?? $defaultValue;
        }

        public function getEnvironmentName() : string {
            return $this -> environmentName;
        }

        public function getHeader(string $name) {
            return $this -> headers[$name] ?? null;
        }

        public function getLanguageCodes() : array {
            $languageHeader = $this -> getHeader('Accept-Language');
            return $this -> parseHeader($languageHeader) ?? [];
        }

        public function getMethod() : string {
            return $this -> method;
        }

        public function getParameter(string $name, $defaultValue = null) : string {
            return $this -> parameters[$name] ?? $defaultValue;
        }

        public function getParameters() : array {
            return $this -> parameters;
        }

        public function getSessionParameter(string $name, $defaultValue = null) : string {
            return $this -> sessionParameters[$name] ?? $defaultValue;
        }

        public function getSessionParameters() : array {
            return $this -> sessionParameters;
        }

        public function hasParameter(string $name) : bool {
            return isset($this -> parameters[$name]);
        }

        public function hasSessionParameter(string $name) : bool {
            return isset($this -> sessionParameters[$name]);
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
        protected function matchTokens(array $pathTokens, array $patternTokens) {
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
        protected function parseHeader(string $headerString) : array {
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

        public function pathMatches(string $path) : bool {
            return $this -> path == $path;
        }

        public function pathMatchesPattern(string $pattern) {
            $pathTokens = explode('/', $this -> path);
            $patternTokens = explode('/', $pattern);
            if (count($pathTokens) != count($patternTokens)) {
                return false;
            }
            return $this -> matchTokens($pathTokens, $patternTokens);
        }

        public function pathStartsWith(string $prefix) : bool {
            $length = strlen($prefix);
            return substr($this -> path, 0, $length) == $prefix;
        }

        public function pathStartsWithPattern(string $pattern) {
            $pathTokens = explode('/', $this -> path);
            $patternTokens = explode('/', $pattern);
            return $this -> matchTokens($pathTokens, $patternTokens);
        }

        public function preconditionFulfilled(Entity $entity, Cache $cache) : bool {
            $matchHeader = $this -> getHeader('If-Match');
            $modifiedSinceHeader = $this -> getHeader('If-Modified-Since');
            $noneMatchHeader = $this -> getHeader('If-None-Match');
            $unmodifiedSinceHeader = $this -> getHeader('If-Unmodified-Since');

            if (is_null($matchHeader) and
                is_null($modifiedSinceHeader) and
                is_null($noneMatchHeader) and
                is_null($unmodifiedSinceHeader)) {

                return true;
            }

            $cacheEntry = $entity -> load($cache);

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

        public function getRemoteIpAddress() : string {
            return $this -> ip;
        }

        public function getBody() : string {
            return $this -> body;
        }

        public function getHost() : string {
            return $this -> host;
        }

        public function getPath() : string {
            return $this -> path;
        }

        public function jsonSerialize() {
            return [
                'body'              => $this -> body,
                'cookies'           => $this -> cookies,
                'environmentName'   => $this -> environmentName,
                'headers'           => $this -> headers,
                'ip'                => $this -> ip,
                'method'            => $this -> method,
                'path'              => $this -> path,
                'parameters'        => $this -> parameters,
                'sessionParameters' => $this -> sessionParameters,
                'host'              => $this -> host
            ];
        }

        public function __toString() {
            return json_encode($this, JSON_PRETTY_PRINT);
        }
    }
}