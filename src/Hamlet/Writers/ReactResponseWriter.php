<?php

namespace Hamlet\Writers;

use Exception;
use GuzzleHttp\Psr7\BufferStream;
use Hamlet\Requests\Request;
use React\Http\Response;
use SessionHandlerInterface;

class ReactResponseWriter implements ResponseWriter
{
    /** @var Response */
    private $response;

    /** @var SessionHandlerInterface|null */
    private $sessionHandler;

    public function __construct(SessionHandlerInterface $sessionHandler = null)
    {
        $this->response = new Response(200, [
            'Server' => 'ReactPHP'
        ]);
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * @param int $code
     * @param string|null $line
     * @return void
     */
    public function status(int $code, string $line = null)
    {
        $this->response = $this->response->withStatus($code);
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function header(string $key, string $value)
    {
        $this->response = $this->response->withHeader($key, $value);
    }

    /**
     * @param string $payload
     * @return void
     */
    public function writeAndEnd(string $payload)
    {
        $body = new BufferStream();
        $body->write($payload);

        $this->response = $this->response->withBody($body);
    }

    public function end()
    {
    }

    /**
     * @param Request $request
     * @param array $sessionParams
     * @return void
     * @throws Exception
     */
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

            $lifeTime = $params['lifetime'] ? time() + ((int) $params['lifetime']) : 0;
            $this->cookie($sessionName, $sessionId, $lifeTime, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        $this->sessionHandler->write($sessionId, serialize($sessionParams));
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return void
     */
    public function cookie(string $name, string $value, int $expires, string $path, string $domain = '', bool $secure = false, bool $httpOnly = false)
    {
        $header = urlencode($name) . '=' . urlencode($value) . '; Path=' . $path;
        if ($expires) {
            $header .= '; Expires=' . date('D, d M Y, H:i:s \G\M\T', $expires);
        }
        if (!empty($domain)) {
            $header .= '; Domain=' . urlencode($domain);
        }
        if (!$secure) {
            $header .= '; Secure';
        }
        if ($httpOnly) {
            $header .= '; HttpOnly';
        }
        $this->response = $this->response->withAddedHeader('Set-Cookie', $header);
    }

    public function response(): Response
    {
        return $this->response;
    }
}
