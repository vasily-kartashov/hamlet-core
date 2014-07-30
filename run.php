#!/usr/bin/env php
<?php

$paths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../autoload.php',
];
foreach ($paths as $path) {
    if (file_exists($path)) {
        /** @noinspection PhpIncludeInspection */
        require_once($path);
        break;
    }
}

$application = new \Symfony\Component\Console\Application();

$application->add(new \Hamlet\Command\RunTestsCommand(realpath(__DIR__ . '/test')));
$application->add(new \Hamlet\Command\AuthorizeClientForGoogleServicesCommand());
$application->add(new \Hamlet\Command\GenerateLongLivedFacebookAccessTokenCommand());

$application->run();