<?php

namespace Hamlet\Requests;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use function base64_encode;
use function function_exists;
use function getallheaders;
use function Hamlet\Cast\_int;
use function Hamlet\Cast\_string;
use function str_replace;
use function strtolower;
use function substr;
use function ucwords;

class Normalizer
{
    /**
     * @param array<string,mixed> $serverParams
     * @return array<string>
     */
    public static function readHeadersFromSuperGlobals(array $serverParams): array
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $value) {
                $headers[(string) $key] = (string) $value;
            }
        } else {
            $aliases = [
                'CONTENT_TYPE' => 'Content-Type',
                'CONTENT_LENGTH' => 'Content-Length',
                'CONTENT_MD5' => 'Content-MD5',
                'REDIRECT_HTTP_AUTHORIZATION' => 'Authorization',
                'PHP_AUTH_DIGEST' => 'Authorization',
            ];
            foreach ($serverParams as $name => $value) {
                if (substr($name, 0, 5) == "HTTP_") {
                    $headerName = str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    );
                    $headers[$headerName] = (string) $value;
                } elseif (isset($aliases[$name]) and !isset($headers[$aliases[$name]])) {
                    $headers[$aliases[$name]] = (string) $value;
                }
            }
            if (!isset($headers['Authorization']) and isset($serverParams['PHP_AUTH_USER'])) {
                $user = (string) $serverParams['PHP_AUTH_USER'];
                $password = isset($serverParams['PHP_AUTH_PW']) ? (string) $serverParams['PHP_AUTH_PW']: '';

                $headers['Authorization'] = 'Basic ' . base64_encode($user . ':' . $password);
            }
        }
        return $headers;
    }

    /**
     * Get a Uri populated with values from server params.
     * @param array<string,mixed> $serverParams
     * @return UriInterface
     */
    public static function getUriFromGlobals(array $serverParams): UriInterface
    {
        $uri = new Uri('');

        $uri = $uri->withScheme(isset($serverParams['HTTPS']) && $serverParams['HTTPS'] !== 'off' ? 'https' : 'http');

        $hasPort = false;
        if (isset($serverParams['HTTP_HOST'])) {
            $hostHeaderParts = explode(':', _string()->cast($serverParams['HTTP_HOST']), 2);
            $uri = $uri->withHost($hostHeaderParts[0]);
            if (count($hostHeaderParts) > 1) {
                $hasPort = true;
                $uri = $uri->withPort((int) $hostHeaderParts[1]);
            }
        } elseif (isset($serverParams['SERVER_NAME'])) {
            $uri = $uri->withHost(_string()->cast($serverParams['SERVER_NAME']));
        } elseif (isset($serverParams['SERVER_ADDR'])) {
            $uri = $uri->withHost(_string()->cast($serverParams['SERVER_ADDR']));
        }

        if (!$hasPort && isset($serverParams['SERVER_PORT'])) {
            $uri = $uri->withPort(_int()->cast($serverParams['SERVER_PORT']));
        }

        $hasQuery = false;
        if (isset($serverParams['REQUEST_URI'])) {
            $requestUriParts = explode('?', _string()->cast($serverParams['REQUEST_URI']), 2);
            $uri = $uri->withPath($requestUriParts[0]);
            if (count($requestUriParts) > 1) {
                $hasQuery = true;
                $uri = $uri->withQuery($requestUriParts[1]);
            }
        }

        if (!$hasQuery && isset($serverParams['QUERY_STRING'])) {
            $uri = $uri->withQuery(_string()->cast($serverParams['QUERY_STRING']));
        }

        return $uri;
    }

    /**
     * @param string|null $version
     * @return string
     */
    public static function extractVersion(?string $version): string
    {
        return $version !== null ? str_replace('HTTP/', '', $version) : '1.1';
    }
}
