<?php

namespace Hamlet\Templating;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class TwigRendererTest extends TestCase
{
    public function testVariableSubstitution()
    {
        $renderer = new TwigRenderer();
        $data = [
            "name" => "World"
        ];
        $path = realpath(__DIR__ . '/variable-substitution.twig');
        Assert::assertEquals($renderer->render($data, $path), "Hello, World!");
    }
}
