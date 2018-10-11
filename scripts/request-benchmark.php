<?php

require_once __DIR__ . '/../vendor/autoload.php';

$runs = 1000000;

$start = microtime(true);
for ($i = 0; $i < $runs; $i++) {
    $request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
}
$end = microtime(true);

echo 'Guzzle: ' . ($end - $start) . PHP_EOL;

$start = microtime(true);
for ($i = 0; $i < $runs; $i++) {
    $request = \Hamlet\Requests\Request::fromSuperGlobals();
}
$end = microtime(true);

echo 'Hamlet: ' . ($end - $start) . PHP_EOL;
