<?php
namespace Hamlet\Requests {

    use Hamlet\Cache\Cache;
    use Hamlet\Entities\Entity;
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
         * @param string $path the value to be matched against
         * @return bool
         */
        public function pathMatches(string $path) : bool;

        /**
         * Check if the request path matches the pattern
         * @todo better description of options here
         *
         * @param string $pattern the pattern to be matched against
         * @return string[]|bool
         */
        public function pathMatchesPattern(string $pattern);

        /**
         * Check if the request path starts with path provided
         *
         * @param string $prefix
         * @return bool
         */
        public function pathStartsWith(string $prefix) : bool;

        /**
         * Check if the request path starts with pattern
         *
         * @param string $pattern
         * @return string[]|bool
         */
        public function pathStartsWithPattern(string $pattern);

        /**
         * Check if the request fulfills the preconditions for embedding the entity into response.
         *
         * @param Entity $entity entity to be checked
         * @param Cache $cache cache provider to check the latest known value of the entity
         * @return bool true if precondition is fulfilled, as in ETag doesn't match, or entity is expired
         */
        public function preconditionFulfilled(Entity $entity, Cache $cache) : bool;
    }
}