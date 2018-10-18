<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\ReactResponseWriter;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;

final class ReactBootstrap
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
        $server = new HttpServer(function (ServerRequestInterface $serverRequest) use ($application) {
            $request = Request::fromServerRequest($serverRequest, $application->sessionHandler());
            $response = $application->run($request);
            $writer = new ReactResponseWriter($application->sessionHandler());
            $application->output($request, $response, $writer);
            return $writer->response();
        });

        $loop = Factory::create();
        $socket = new SocketServer($host . ':' . $port, $loop);
        $server->listen($socket);

        $loop->run();
    }
}
