<?php

namespace Hamlet\Entities;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class PlainTextEntityTest extends TestCase
{
    public function testKeyGeneration()
    {
        $entity = new PlainTextEntity('content');

        Assert::assertTrue(is_string($entity->getKey()));
        Assert::assertNotEmpty($entity->getKey());
        Assert::assertNotEmpty($entity->getContent());
    }
}
