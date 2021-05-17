<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\DefaultResponseWriter;

final class ServerBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param AbstractApplication $application
     * @return void
     */
    public static function run(AbstractApplication $application): void
    {
        $request = Request::fromSuperGlobals($application->sessionHandler());
        $writer = new DefaultResponseWriter();
        $response = $application->run($request);
        $application->output($request, $response, $writer);
    }
}
