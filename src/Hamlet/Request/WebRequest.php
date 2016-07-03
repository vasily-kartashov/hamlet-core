<?php

namespace Hamlet\Request {

    class WebRequest extends BasicRequest
    {

        public function __construct()
        {
            parent::__construct(null, null, null, null, null, null, null, null);
            $this->environmentName = $_SERVER['SERVER_NAME'];
            $this->method = $_SERVER['REQUEST_METHOD'];
            $body = file_get_contents('php://input');
            if ($body) {
                $this->body = $body;
            } else {
                $this->body = null;
            }

            if ($this->method == 'GET' or $this->method == 'POST') {
                $this->parameters = $_REQUEST;
            } else {
                parse_str($body, $this->parameters);
            }

            if (function_exists('getallheaders')) {
                $this->headers = getallheaders();
            }
            $this->cookies = $_COOKIE;
            $this->ip = $this->headers['X-Forwarded-For'] ?? $_SERVER['REMOTE_ADDR'];
            $this->host = $_SERVER['HTTP_HOST'] ?? null;
            $completePath = urldecode($_SERVER['REQUEST_URI']);
            $questionMarkPosition = strpos($completePath, '?');
            if ($questionMarkPosition === false) {
                $this->path = $completePath;
            } else {
                $this->path = substr($completePath, 0, $questionMarkPosition);
            }
        }

        public function getSessionParameter(string $name, $defaultValue = null) : string
        {
            assert(is_string($name));
            $this->startSession();
            return parent::getSessionParameter($name, $defaultValue);
        }

        public function getSessionParameters() : array
        {
            $this->startSession();
            return parent::getSessionParameters();
        }

        protected function startSession() : void
        {
            if (!session_id()) {
                session_start();
                $this->sessionParameters = $_SESSION ?? [];
            }
        }
    }
}