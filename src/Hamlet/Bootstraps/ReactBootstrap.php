<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\ReactResponseWriter;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use RuntimeException;

final class ReactBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param string $uri
     * @param callable $applicationProvider
     * @return void
     */
    public static function run(string $uri, callable $applicationProvider)
    {
        $application = $applicationProvider();
        if (!($application instanceof AbstractApplication)) {
            throw new RuntimeException('Application required');
        }

        $server = new HttpServer(function (ServerRequestInterface $serverRequest) use ($application) {
            $request = Request::fromServerRequest($serverRequest);
            $response = $application->run($request);
            $writer = new ReactResponseWriter();
            $application->output($request, $response, $writer);
            return $writer->response();
        });

        $loop = Factory::create();
        $socket = new SocketServer($uri, $loop);
        $server->listen($socket);

        $loop->run();
    }
}
