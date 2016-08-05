<?php

namespace Hamlet\TestRunner {

    interface TestRunnerInterface
    {
        /**
         * Execute tests and return true if successful
         */
        public function execute(string $rootDirectoryPath, string $className) : bool;
    }
}