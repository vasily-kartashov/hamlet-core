<?php

namespace Hamlet\TestRunners {

    interface TestRunner {

        public function execute(string $rootDirectoryPath, string $className) : bool;
    }
}