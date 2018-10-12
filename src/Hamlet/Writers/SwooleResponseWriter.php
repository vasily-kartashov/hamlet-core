<?php

namespace Hamlet\Writers;

use Hamlet\Requests\Request;
use SessionHandlerInterface;
use Swoole\Http\Response;

class SwooleResponseWriter implements ResponseWriter
{
    /** @var Response */
    private $response;

    /** @var SessionHandlerInterface|null */
    private $sessionHandler;

    public function __construct(Response $response, SessionHandlerInterface $sessionHandler = null)
    {
        $this->response = $response;
        $this->sessionHandler = $sessionHandler;
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

    public function session(Request $request, array $sessionParams)
    {
        if ($this->sessionHandler === null) {
            return;
        }

        $sessionName = session_name();
        $cookies = $request->getCookieParams();

        if (isset($cookies[$sessionName])) {
            $sessionId = $cookies[$sessionName];
        } else {
            $params = session_get_cookie_params();
            $sessionId = \bin2hex(\random_bytes(8));

            $lifeTime = $params['lifetime'] ? time() + ((int) $params['lifetime']) : time();
            $this->cookie($sessionName, $sessionId, $lifeTime, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        $this->sessionHandler->write($sessionId, serialize($sessionParams));
    }
}
