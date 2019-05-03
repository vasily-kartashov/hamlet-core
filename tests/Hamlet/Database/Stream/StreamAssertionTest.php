<?php

namespace Hamlet\Database\Stream;

use Hamlet\Database\Processing\ProcessorAssertionTest;

class StreamAssertionTest extends ProcessorAssertionTest
{
    protected function phones()
    {
        $records = parent::collector()->collectAll();
        $generator = function () use ($records) {
            foreach ($records as $key => $value) {
                yield [$key, $value];
            }
        };
        return new Collector($generator);
    }
}
