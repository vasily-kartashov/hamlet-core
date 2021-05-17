<?php

namespace Hamlet\Writers;

use Hamlet\Requests\Request;

class DefaultResponseWriter implements ResponseWriter
{
    public function status(int $code, string $line = null): void
    {
        if ($line !== null) {
            header($line);
        }
    }

    public function header(string $key, string $value): void
    {
        header($key . ': ' . $value);
    }

    public function writeAndEnd(string $payload): void
    {
        echo $payload;
        exit;
    }

    public function end(): void
    {
        exit;
    }

    public function cookie(string $name, string $value, int $expires, string $path, string $domain = '', bool $secure = false, bool $httpOnly = false): void
    {
        setcookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    public function session(Request $request, array $params): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        foreach ($params as $name => $value) {
            $_SESSION[$name] = $value;
        }
    }
}
