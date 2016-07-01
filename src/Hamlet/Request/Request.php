<?php
namespace Hamlet\Request {

    use Hamlet\Cache\Cache;
    use Hamlet\Entity\Entity;
    use \JsonSerializable;

    interface Request extends JsonSerializable {

        public function environmentNameEndsWith(string $suffix) : bool;

        public function getCookie(string $name, $defaultValue = null) : string;

        public function getEnvironmentName() : string;

        public function getHeader(string $headerName) : string;

        /**
         * @return string[]
         */
        public function getLanguageCodes() : array;

        public function getMethod() : string;

        public function getParameter(string $name, $defaultValue = null) : string;

        public function getBody() : string;

        public function getPath() : string;

        /**
         * @return string[]
         */
        public function getParameters() : array;

        /**
         * @return string
         */
        public function getRemoteIpAddress() : string;

        /**
         * @param string $name
         * @param string $defaultValue
         * @return string|null
         */
        public function getSessionParameter(string $name, $defaultValue = null) : string;

        public function getSessionParameters() : array;

        public function hasParameter(string $name) : bool;

        public function hasSessionParameter(string $name) : bool;

        /**
         * Check if the request path matches exactly provided string
         */
        public function pathMatches(string $path) : bool;

        /**
         * Check if the request path matches the pattern
         *
         * @param string $pattern
         *
         * @return string[]|bool
         */
        public function pathMatchesPattern(string $pattern);

        /**
         * Check if the request path starts with path provided
         *
         * @param string $prefix
         *
         * @return bool
         */
        public function pathStartsWith(string $prefix) : bool;

        /**
         * Check if the request path starts with pattern
         *
         * @param string $pattern
         *
         * @return string[]|bool
         */
        public function pathStartsWithPattern(string $pattern);

        /**
         * Check if the request fulfills the preconditions for conditional request
         */
        public function preconditionFulfilled(Entity $entity, Cache $cache) : bool;
    }
}