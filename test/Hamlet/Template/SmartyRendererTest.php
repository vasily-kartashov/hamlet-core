<?php

namespace Hamlet\Template;

use UnitTestCase;

class SmartyRendererTest extends UnitTestCase
{
    public function testVariableSubstitution()
    {
        $renderer = new SmartyRenderer();
        $data = [
            "name" => "World"
        ];
        $path = realpath(__DIR__ . '/variable-substitution.tpl');
        $this->assertEqual($renderer->render($data, $path), "Hello, World!");
    }
}