#!/usr/bin/env php
<?php

require_once(__DIR__ . '/vendor/autoload.php');

$application = new \Symfony\Component\Console\Application();

$application->add(new \Hamlet\Command\RunTestsCommand(realpath(__DIR__ . '/test')));
$application->add(new \Hamlet\Command\AddGoogleProfileCommand());
$application->add(new \Hamlet\Command\ListGoogleProfilesCommand());

$application->run();