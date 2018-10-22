<?php

namespace Hamlet\Entities;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class JsonEntityTest extends TestCase
{
    public function testKeyGeneration()
    {
        $entity = new JsonEntity([
            'x' => 12
        ]);

        Assert::assertTrue(is_string($entity->getKey()));
        Assert::assertNotEmpty($entity->getKey());
        Assert::assertJson($entity->getContent());
    }
}
