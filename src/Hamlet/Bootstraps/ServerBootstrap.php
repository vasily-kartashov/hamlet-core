<?php

namespace Hamlet\Bootstraps;

use Hamlet\Applications\AbstractApplication;
use Hamlet\Requests\Request;
use Hamlet\Writers\DefaultResponseWriter;
use RuntimeException;

final class ServerBootstrap
{
    private function __construct()
    {
    }

    /**
     * @param callable $applicationProvider
     * @return void
     */
    public static function run(callable $applicationProvider)
    {
        $application = $applicationProvider();
        if (!($application instanceof AbstractApplication)) {
            throw new RuntimeException('Application required');
        }
        $request = Request::fromSuperGlobals();
        $writer = new DefaultResponseWriter();
        $response = $application->run($request);
        $application->output($request, $response, $writer);
    }
}
