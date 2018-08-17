<?php

namespace Hamlet\Writers;

use Hamlet\Requests\Request;
use Swoole\Http\Response;

class SwooleResponseWriter implements ResponseWriter
{
    /** @var Response */
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function status(int $code, string $line = null)
    {
        $this->response->status($code);
    }

    public function header(string $key, string $value)
    {
        $this->response->header($key, $value);
    }

    public function write(string $payload)
    {
        $this->response->write($payload);
    }

    public function cookie(string $name, string $value, int $expires, string $path, string $domain = '', bool $secure = false, bool $httpOnly = false)
    {
        $this->response->cookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    public function end()
    {
        $this->response->end();
    }

    public function session(Request $request, array $params)
    {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        $cookies = $request->getCookieParams();

        if (isset($cookies[session_name()])) {
            session_id($cookies[session_name()]);
        } else {
            $params = session_get_cookie_params();
            if (session_id()) {
                session_id(\bin2hex(\random_bytes(32)));
            }
            $_SESSION = [];
            $lifeTime = $params['lifetime'] ? time() + ((int) $params['lifetime']) : time();
            $this->cookie(session_name(), session_id(), $lifeTime, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        foreach ($params as $name => $value) {
            $_SESSION[$name] = $value;
        }
    }
}
