<?php

namespace Hamlet\Writers;

use GuzzleHttp\Psr7\BufferStream;
use Hamlet\Requests\Request;
use React\Http\Response;

class ReactResponseWriter implements ResponseWriter
{
    /** @var Response */
    private $response;

    public function __construct()
    {
        $this->response = new Response(200, [
            'Server' => 'ReactPHP'
        ]);
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
    public function write(string $payload)
    {
        $body = new BufferStream();
        $body->write($payload);

        $this->response = $this->response->withBody($body);
    }

    /**
     * @param Request $request
     * @param array $params
     * @return void
     */
    public function session(Request $request, array $params)
    {
        // TODO: Implement session() method.
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
        // TODO: Implement cookie() method.
    }

    /**
     * @return void
     */
    public function end()
    {
    }

    public function response(): Response
    {
        return $this->response;
    }
}
