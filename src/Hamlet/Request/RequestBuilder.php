<?php

namespace Hamlet\Request {

    class RequestBuilder
    {
        /** @var string[] */
        protected $cookies = [];

        /** @var string */
        protected $environmentName = 'localhost';

        /** @var string[] */
        protected $headers = [];

        /** @var string */
        protected $ip = '127.0.0.1';

        /** @var string */
        protected $method = 'GET';

        /** @var string */
        protected $path = '/';

        /** @var  string[] */
        protected $parameters = [];

        /** @var string[] */
        protected $sessionParameters = [];

        /**
         * @var string
         */
        protected $body = '';

        protected $host;

        public function setPath(string $path) : RequestBuilder
        {
            $questionMarkPosition = strpos($path, '?');
            if ($questionMarkPosition === false) {
                $this->path = urldecode($path);
            } else {
                $this->path = urldecode(substr($path, 0, $questionMarkPosition));
                parse_str(substr($path, $questionMarkPosition + 1), $parameters);
                $this->setParameters($parameters);
            }
            return $this;
        }

        public function setParameter(string $name, string $value) : RequestBuilder
        {
            assert(is_string($name));
            assert(is_string($value));
            $this->parameters[(string)$name] = (string)$value;
            return $this;
        }

        public function setParameters(array $parameters) : RequestBuilder
        {
            $this->parameters += $parameters;
            return $this;
        }

        public function build() : Request
        {
            return new BasicRequest($this->method, $this->path, $this->environmentName, $this->ip, $this->headers,
                $this->parameters, $this->sessionParameters, $this->cookies, $this->host, $this->body);
        }

        public function parseJSON(string $json) :RequestBuilder
        {
            $data = json_decode($json, true);
            return $this->parseData($data);
        }

        public function parseData(array $data) : RequestBuilder
        {

            if (isset($data['body'])) {
                $this->body = $data['body'];
            }
            if (isset($data['cookies'])) {
                $this->cookies = $data['cookies'];
            }
            if (isset($data['environmentName'])) {
                $this->environmentName = $data['environmentName'];
            }
            if (isset($data['headers'])) {
                $this->headers = $data['headers'];
            }
            if (isset($data['ip'])) {
                $this->ip = $data['ip'];
            }
            if (isset($data['method'])) {
                $this->method = $data['method'];
            }
            if (isset($data['path'])) {
                $this->path = $data['path'];
            }
            if (isset($data['parameters'])) {
                $this->parameters = $data['parameters'];
            }
            if (isset($data['sessionParameters'])) {
                $this->sessionParameters = $data['sessionParameters'];
            }
            if (isset($data['host'])) {
                $this->host = $data['host'];
            }
            return $this;
        }
    }
}