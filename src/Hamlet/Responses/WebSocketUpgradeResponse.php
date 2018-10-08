<?php

namespace Hamlet\Responses;

class WebSocketUpgradeResponse extends Response
{
    public function __construct(string $acceptHash)
    {
        parent::__construct(101, null, false, [
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $acceptHash,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off'
        ]);
    }
}
