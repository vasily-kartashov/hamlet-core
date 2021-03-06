<?php

namespace Hamlet\Writers;

use Exception;
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

    /**
     * @param int $code
     * @param string|null $line
     * @suppress PhanParamTooManyInternal
     */
    public function status(int $code, string $line = null)
    {
        $this->response->status((string) $code);
    }

    public function header(string $key, string $value)
    {
        // @bug not sure but for whatever reason swoole dislikes this header
        if (strtolower($key) == 'content-length') {
            return;
        }
        $this->response->header($key, $value);
    }

    public function writeAndEnd(string $payload)
    {
        $this->response->end($payload);
    }

    public function end()
    {
        $this->response->end();
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @psalm-suppress InvalidScalarArgument
     */
    public function cookie(string $name, string $value, int $expires, string $path, string $domain = '', bool $secure = false, bool $httpOnly = false)
    {
        $this->response->cookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    /**
     * @param Request $request
     * @param array $params
     * @throws Exception
     */
    public function session(Request $request, array $params)
    {
        if ($this->sessionHandler === null) {
            return;
        }

        $sessionName = session_name();
        $cookies = $request->getCookieParams();

        if (isset($cookies[$sessionName])) {
            $sessionId = $cookies[$sessionName];
        } else {
            $cookieParams = session_get_cookie_params();
            $sessionId = \bin2hex(\random_bytes(8));

            $lifeTime = $cookieParams['lifetime'] ? time() + ((int) $cookieParams['lifetime']) : time();
            $this->cookie($sessionName, $sessionId, $lifeTime, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
        }

        $this->sessionHandler->write($sessionId, serialize($params));
    }
}
