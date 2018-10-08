<?php

namespace Hamlet\Resources;

use Hamlet\Entities\PlainTextEntity;
use Hamlet\Requests\Request;
use Hamlet\Responses\BadRequestResponse;
use Hamlet\Responses\Response;
use Hamlet\Responses\WebSocketUpgradeResponse;

/**
 * WebSocket handshake resource
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Protocol_upgrade_mechanism
 */
class WebSocketHandshakeResource implements WebResource
{
    public function getResponse(Request $request): Response
    {
        if ((float) $request->getProtocolVersion() < 1.1) {
            return new BadRequestResponse(new PlainTextEntity('Protocol version must be "1.1" or higher'));
        }

        if ($request->getMethod() !== 'GET') {
            return new BadRequestResponse(new PlainTextEntity('HTTP method must be "GET"'));
        }

        $connectionHeaders = $request->getHeader('Connection');
        if (empty($connectionHeaders) || !in_array('Upgrade', $connectionHeaders)) {
            return new BadRequestResponse(new PlainTextEntity('The Connection header must be set to "Upgrade"'));
        }

        $upgradeHeaders = $request->getHeader('Upgrade');
        if (empty($upgradeHeaders) || strtolower($upgradeHeaders[0]) !== 'websocket') {
            return new BadRequestResponse(new PlainTextEntity('The Upgrade header must be set to "websocket"'));
        }

        $keys = $request->getHeader('Sec-WebSocket-Key');
        if (empty($keys)) {
            return new BadRequestResponse(new PlainTextEntity('Missing Sec-WebSocket-Key header'));
        }

        $key = $keys[0];
        $pattern = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (!preg_match($pattern, $key)) {
            return new BadRequestResponse(new PlainTextEntity('Invalid Sec-WebSocket-Key format'));
        }

        $decodedKey = base64_decode($key);
        if (!$decodedKey || strlen($decodedKey) !== 16) {
            return new BadRequestResponse(new PlainTextEntity('Invalid Sec-WebSocket-Key value'));
        }

        $acceptHash = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        return new WebSocketUpgradeResponse($acceptHash);
    }
}
