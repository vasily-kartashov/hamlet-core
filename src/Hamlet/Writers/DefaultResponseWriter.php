<?php

namespace Hamlet\Writers;

use Hamlet\Requests\Request;

class DefaultResponseWriter implements ResponseWriter
{
    public function status(int $code, string $line = null)
    {
        if ($line !== null) {
            header($line);
        }
    }

    public function header(string $key, string $value)
    {
        header($key . ': ' . $value);
    }

    public function write(string $payload)
    {
        echo $payload;
    }

    public function cookie(string $name, string $value, int $expires, string $path, string $domain = '', bool $secure = false, bool $httpOnly = false)
    {
        setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    public function end()
    {
        exit;
    }

    public function session(Request $request, array $params)
    {
        if (session_id() !== null) {
            session_start();
        }
        foreach ($params as $name => $value) {
            $_SESSION[$name] = $value;
        }
    }
}
