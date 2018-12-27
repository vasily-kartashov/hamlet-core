<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\SwooleResponseWriter;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;

final class SwooleBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param string $host
     * @param int $port
     * @param AbstractApplication $application
     * @return void
     */
    public static function run(string $host, int $port, AbstractApplication $application)
    {
        $server = new Server($host, $port, SWOOLE_BASE);
        /** @psalm-suppress ForbiddenCode */
        $workers = (int) shell_exec('grep -c processor /proc/cpuinfo');
        $server->set([
            'worker_num' => $workers
        ]);

        $server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($application) {
            $request = Request::fromSwooleRequest($swooleRequest, $application->sessionHandler());
            $writer = new SwooleResponseWriter($swooleResponse, $application->sessionHandler());
            $response = $application->run($request);
            $application->output($request, $response, $writer);
        });
        $server->start();
    }
}
