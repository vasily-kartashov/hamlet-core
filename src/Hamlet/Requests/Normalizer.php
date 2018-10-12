<?php

namespace Hamlet\Requests;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Swoole\Http\Request as SwooleRequest;

class Normalizer
{
    /**
     * @param array $serverParams
     * @return string[]
     */
    public static function readHeadersFromSuperGlobals(array $serverParams)
    {
        $headers = [];
        if (\function_exists('getallheaders')) {
            foreach (\getallheaders() as $key => &$value) {
                $headers[$key] = (string)$value;
            }
        } else {
            $aliases = [
                'CONTENT_TYPE' => 'Content-Type',
                'CONTENT_LENGTH' => 'Content-Length',
                'CONTENT_MD5' => 'Content-MD5',
                'REDIRECT_HTTP_AUTHORIZATION' => 'Authorization',
                'PHP_AUTH_DIGEST' => 'Authorization',
            ];
            foreach ($serverParams as $name => &$value) {
                if (\substr($name, 0, 5) == "HTTP_") {
                    $headerName = \str_replace(
                        ' ',
                        '-',
                        \ucwords(\strtolower(\str_replace('_', ' ', \substr($name, 5))))
                    );
                    $headers[$headerName] = (string)$value;
                } elseif (isset($aliases[$name]) and !isset($headers[$aliases[$name]])) {
                    $headers[$aliases[$name]] = (string)$value;
                }
            }
            if (!isset($headers['Authorization']) and isset($serverParams['PHP_AUTH_USER'])) {
                $password = $serverParams['PHP_AUTH_PW'] ?? '';
                $headers['Authorization'] = 'Basic ' . \base64_encode($serverParams['PHP_AUTH_USER'] . ':' . $password);
            }
        }
        return $headers;
    }

    public static function uriFromSwooleRequest(SwooleRequest $request): UriInterface
    {
        $uri = new Uri('');
        $uri = $uri->withScheme(!empty($request->server['HTTPS']) && $request->server['HTTPS'] !== 'off' ? 'https' : 'http');

        $hasPort = false;
        $hostHeaderParts = explode(':', $request->server['host'] ?? 'localhost');
        $uri = $uri->withHost($hostHeaderParts[0]);
        if (isset($hostHeaderParts[1])) {
            $hasPort = true;
            $uri = $uri->withPort((int) $hostHeaderParts[1]);
        }

        if (!$hasPort && isset($request->server['server_port'])) {
            $uri = $uri->withPort($request->server['server_port']);
        }

        $hasQuery = false;
        if (isset($request->server['request_uri'])) {
            $requestUriParts = explode('?', $request->server['request_uri']);
            /** @var Uri $uri */
            $uri = $uri->withPath($requestUriParts[0]);
            if (isset($requestUriParts[1])) {
                $hasQuery = true;
                $uri = $uri->withQuery($requestUriParts[1]);
            }
        }

        if (!$hasQuery && isset($request->server['query_string'])) {
            $uri = $uri->withQuery($request->server['query_string']);
        }

        return $uri;
    }

    /**
     * Get a Uri populated with values from server params.
     * @param array $serverParams
     * @return UriInterface
     */
    public static function getUriFromGlobals(array $serverParams): UriInterface
    {
        $uri = new Uri('');

        $uri = $uri->withScheme(!empty($serverParams['HTTPS']) && $serverParams['HTTPS'] !== 'off' ? 'https' : 'http');

        $hasPort = false;
        if (isset($serverParams['HTTP_HOST'])) {
            $hostHeaderParts = explode(':', $serverParams['HTTP_HOST'], 2);
            $uri = $uri->withHost($hostHeaderParts[0]);
            if (count($hostHeaderParts) > 1) {
                $hasPort = true;
                $uri = $uri->withPort((int) $hostHeaderParts[1]);
            }
        } elseif (isset($serverParams['SERVER_NAME'])) {
            $uri = $uri->withHost($serverParams['SERVER_NAME']);
        } elseif (isset($serverParams['SERVER_ADDR'])) {
            $uri = $uri->withHost($serverParams['SERVER_ADDR']);
        }

        if (!$hasPort && isset($serverParams['SERVER_PORT'])) {
            $uri = $uri->withPort($serverParams['SERVER_PORT']);
        }

        $hasQuery = false;
        if (isset($serverParams['REQUEST_URI'])) {
            $requestUriParts = explode('?', $serverParams['REQUEST_URI'], 2);
            $uri = $uri->withPath($requestUriParts[0]);
            if (count($requestUriParts) > 1) {
                $hasQuery = true;
                $uri = $uri->withQuery($requestUriParts[1]);
            }
        }

        if (!$hasQuery && isset($serverParams['QUERY_STRING'])) {
            $uri = $uri->withQuery($serverParams['QUERY_STRING']);
        }

        return $uri;
    }

    public static function serverParametersFromSwooleRequest(SwooleRequest $request): array
    {
        return array_filter([
            'SERVER_SOFTWARE'       => $request->server['server_software']      ?? null,
            'SERVER_PROTOCOL'       => $request->server['server_protocol']      ?? null,
            'REQUEST_METHOD'        => $request->server['request_method']       ?? null,
            'REQUEST_TIME'          => $request->server['request_time']         ?? null,
            'REQUEST_TIME_FLOAT'    => $request->server['request_time_float']   ?? null,
            'QUERY_STRING'          => $request->server['query_string']         ?? null,
            'HTTP_ACCEPT'           => $request->header['accept']               ?? null,
            'HTTP_ACCEPT_CHARSET'   => $request->header['accept-charset']       ?? null,
            'HTTP_ACCEPT_ENCODING'  => $request->header['accept-encoding']      ?? null,
            'HTTP_ACCEPT_LANGUAGE'  => $request->header['accept-language']      ?? null,
            'HTTP_CONNECTION'       => $request->header['connection']           ?? null,
            'HTTP_HOST'             => $request->header['host']                 ?? null,
            'HTTP_REFERER'          => $request->header['referer']              ?? null,
            'HTTP_USER_AGENT'       => $request->header['user-agent']           ?? null,
            'REMOTE_ADDR'           => $request->server['remote_addr']          ?? null,
            'REMOTE_HOST'           => $request->server['remote_host']          ?? null,
            'REMOTE_PORT'           => $request->server['remote_port']          ?? null,
            'SERVER_PORT'           => $request->server['server_port']          ?? null,
            'REQUEST_URI'           => $request->server['request_uri']          ?? null,
            'PATH_INFO'             => $request->server['path_info']            ?? null,
            'ORIG_PATH_INFO'        => $request->server['path_info']            ?? null
        ]);
    }

    /**
     * @param null|string $version
     * @return string
     */
    public static function extractVersion($version)
    {
        return $version !== null ? str_replace('HTTP/', '', $version) : '1.1';
    }
}
