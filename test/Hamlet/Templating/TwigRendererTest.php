<?php

namespace Hamlet\Templating;

use UnitTestCase;

class TwigRendererTest extends UnitTestCase
{
    public function testVariableSubstitution()
    {
        $renderer = new TwigRenderer();
        $data = [
            "name" => "World"
        ];
        $path = realpath(__DIR__ . '/variable-substitution.twig');
        $this->assertEqual($renderer->render($data, $path), "Hello, World!");
    }
}
