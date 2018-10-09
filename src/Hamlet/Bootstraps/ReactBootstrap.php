<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\ReactResponseWriter;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\StreamingServer;
use React\Socket\Server as SocketServer;
use RuntimeException;

final class ReactBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param int $port
     * @param callable $applicationProvider
     * @return void
     */
    public static function run(int $port, callable $applicationProvider)
    {
        $application = $applicationProvider();
        if (!($application instanceof AbstractApplication)) {
            throw new RuntimeException('Application required');
        }

        $loop = Factory::create();
        $server = new StreamingServer(function (ServerRequestInterface $serverRequest) use ($application) {
            $request = Request::fromServerRequest($serverRequest);
            $response = $application->run($request);
            $writer = new ReactResponseWriter();
            $application->output($request, $response, $writer);
            return $writer->response();
        });

        $socket = new SocketServer($port, $loop);
        $server->listen($socket);

        $loop->run();
    }
}
