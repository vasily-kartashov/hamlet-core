<?php

namespace Hamlet\Responses;

use Hamlet\Entities\JsonEntity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ResponseBuilderTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testResponseBuilder()
    {
        $response = ResponseBuilder::create()
            ->withStatusCode(200)
            ->withEntity(new JsonEntity("hey there"))
            ->withHeader('Cache-Control', 'none')
            ->withSessionParam('userId', 1)
            ->build();

        $type = new ReflectionClass(get_class($response));

        $statusCode = $type->getProperty('statusCode');
        $statusCode->setAccessible(true);
        Assert::assertEquals(200, $statusCode->getValue($response));

        $headers = $type->getProperty('headers');
        $headers->setAccessible(true);
        Assert::assertEquals(['Cache-Control' => 'none'], $headers->getValue($response));

        $embedEntity = $type->getProperty('embedEntity');
        $embedEntity->setAccessible(true);
        Assert::assertTrue($embedEntity->getValue($response));

        $session = $type->getProperty('sessionParams');
        $session->setAccessible(true);
        Assert::assertEquals(['userId' => 1], $session->getValue($response));
    }
}
