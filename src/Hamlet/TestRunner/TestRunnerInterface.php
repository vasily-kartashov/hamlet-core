<?php

namespace Hamlet\TestRunner;

interface TestRunnerInterface
{
    /**
     * Execute tests and return true if successful
     *
     * @param string $rootDirectoryPath
     * @param string $className
     *
     * @return bool
     */
    public function execute($rootDirectoryPath, $className);
}