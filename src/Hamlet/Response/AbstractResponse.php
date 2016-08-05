<?php

namespace Hamlet\Response {

    use Hamlet\Cache\Cache;
    use Hamlet\Entity\Entity;
    use Hamlet\Request\Request;

    /**
     * Response classes should be treated as immutable although they are clearly not. The current design makes it
     * developer's responsibility to make sure that the response objects are always well-formed.
     */
    class AbstractResponse implements Response {

        /** @var string */
        protected $status;

        /** @var string[] */
        protected $headers = [];

        /** @var \Hamlet\Entity\Entity */
        protected $entity = null;

        /** @var bool */
        protected $embedEntity = true;

        /**
         * @var array {
         *      string $name
         *      string $value
         *      string $path
         *      int $timeToLive
         * }
         */
        protected $cookies = [];

        /** @var array */
        protected $session = [];

        protected function __construct(string $status = '') {
            $this -> status = $status;
        }

        public function getStatus() : string {
            return $this -> status;
        }

        protected function setStatus(string $status) {
            $this -> status = $status;
        }

        protected function setEntity(Entity $entity) {
            $this -> entity = $entity;
        }

        protected function setEmbedEntity(bool $embedEntity) {
            $this -> embedEntity = $embedEntity;
        }

        public function output(Request $request, Cache $cache) : void {
            if (count($this -> session) > 0) {
                if (!session_id()) {
                    session_start();
                }
                foreach ($this -> session as $name => $value) {
                    $_SESSION[$name] = $value;
                }
            }

            header('HTTP/1.1 ' . $this -> status);
            foreach ($this -> headers as $name => $content) {
                header($name . ': ' . $content);
            }

            foreach ($this -> cookies as $cookie) {
                setcookie($cookie['name'], $cookie['value'], time() + $cookie['timeToLive'], $cookie['path']);
            }

            if (!is_null($this -> entity)) {
                $cacheEntry = $this -> entity -> load($cache);
                $now = time();
                $maxAge = max(0, $cacheEntry['expires'] - $now);

                header('ETag: ' . $cacheEntry['tag']);
                header('Last-Modified: ' . $this -> formatTimestamp($cacheEntry['modified']));
                header('Cache-Control: public, max-age=' . $maxAge);
                header('Expires: ' . $this -> formatTimestamp($now + $maxAge));

                if ($this -> embedEntity) {
                    header('Content-Type: ' . $this -> entity -> getMediaType());
                    header('Content-Length: ' . $cacheEntry['length']);
                    header('Content-MD5: ' . $cacheEntry['digest']);
                    $language = $this -> entity -> getContentLanguage();
                    if ($language) {
                        header('Content-Language: ' . $language);
                    }
                    echo $cacheEntry['content'];
                }
            }

            exit;
        }

        public function setHeader(string $headerName, string $headerValue) {
            $this -> headers[$headerName] = $headerValue;
        }

        public function setCookie(string $name, string $value, string $path, int $timeToLive) {
            $this -> cookies[] = [
                'name'       => $name,
                'value'      => $value,
                'path'       => $path,
                'timeToLive' => $timeToLive,
            ];
        }

        public function setSessionParameter(string $name, $value) : void {
            $this -> session[$name] = $value;
        }

        /**
         * Convert timestamp into RFC 822 format
         */
        protected function formatTimestamp($timestamp) : string {
            return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
        }

        public function getEntity() : Entity {
            return $this -> entity;
        }
    }
}
