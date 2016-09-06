<?php

namespace Hamlet\Requests {

    class WebRequest extends BasicRequest {

        public function __construct() {
            parent::__construct(null, null, null, null, null, null, null, null);
            $this -> environmentName = $_SERVER['SERVER_NAME'];
            $this -> method = $_SERVER['REQUEST_METHOD'];
            $body = file_get_contents('php://input');
            if ($body) {
                $this -> body = $body;
            } else {
                $this -> body = null;
            }

            if ($this -> method == 'GET' or $this -> method == 'POST') {
                $this -> parameters = $_REQUEST;
            } else {
                parse_str($body, $this -> parameters);
            }

            $this -> headers = $this->getHeaders();
            $this -> cookies = $_COOKIE;
            $this -> ip = $this -> headers['X-Forwarded-For'] ?? $_SERVER['REMOTE_ADDR'];
            $this -> host = $_SERVER['HTTP_HOST'] ?? null;
            $completePath = urldecode($_SERVER['REQUEST_URI']);
            $questionMarkPosition = strpos($completePath, '?');
            if ($questionMarkPosition === false) {
                $this -> path = $completePath;
            } else {
                $this -> path = substr($completePath, 0, $questionMarkPosition);
            }
        }

        public function getSessionParameter(string $name, $defaultValue = null) : string {
            $this -> startSession();
            return parent::getSessionParameter($name, $defaultValue);
        }

        public function getSessionParameters() : array {
            $this -> startSession();
            return parent::getSessionParameters();
        }

        protected function startSession() {
            if (!session_id()) {
                session_start();
                $this -> sessionParameters = $_SESSION ?? [];
            }
        }

        private function getHeaders() {
            if (function_exists('getallheaders')) {
                return getallheaders();
            }
            $headers = [];
            $aliases = [
                'CONTENT_TYPE'                => 'Content-Type',
                'CONTENT_LENGTH'              => 'Content-Length',
                'CONTENT_MD5'                 => 'Content-MD5',
                'REDIRECT_HTTP_AUTHORIZATION' => 'Authorization',
                'PHP_AUTH_DIGEST'             => 'Authorization',
            ];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == "HTTP_") {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                } elseif (isset($aliases[$name]) and !isset($headers[$aliases[$name]])) {
                    $headers[$aliases[$name]] = $value;
                }
            }
            if (!isset($headers['Authorization']) and isset($_SERVER['PHP_AUTH_USER'])) {
                $password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $password);
            }
            return $headers;
        }
    }
}