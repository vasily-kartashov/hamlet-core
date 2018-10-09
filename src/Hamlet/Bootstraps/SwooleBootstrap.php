<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\SwooleResponseWriter;
use RuntimeException;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\WebSocket\Server;

final class SwooleBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param string $host
     * @param int $port
     * @param callable $applicationProvider
     * @param callable|null $initializer
     * @return void
     */
    public static function run(string $host, int $port, callable $applicationProvider, callable $initializer = null)
    {
        $application = $applicationProvider();
        if (!($application instanceof AbstractApplication)) {
            throw new RuntimeException('Application required');
        }
        $server = new Server($host, $port);
        if ($initializer !== null) {
            $initializer($server);
        }
        $server->on('message', function () {
            // @todo add implementation and all possible callbacks for sockets
        });
        $server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($application) {
            $request = Request::fromSwooleRequest($swooleRequest);
            $writer = new SwooleResponseWriter($swooleResponse);
            $response = $application->run($request);
            $application->output($request, $response, $writer);
        });
        $server->start();
    }
}
