<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\DefaultResponseWriter;
use SessionHandlerInterface;

final class ServerBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param AbstractApplication $application
     * @param SessionHandlerInterface|null $sessionHandler
     * @return void
     */
    public static function run(AbstractApplication $application, SessionHandlerInterface $sessionHandler = null)
    {
        $request = Request::fromSuperGlobals($sessionHandler);
        $writer = new DefaultResponseWriter();
        $response = $application->run($request);
        $application->output($request, $response, $writer);
    }
}
