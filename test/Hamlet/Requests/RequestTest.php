<?php

namespace Hamlet\Requests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Void\VoidCachePool;
use Hamlet\Entities\JsonEntity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testMatchTokens()
    {
        $request = Request::builder()
            ->withUri('/world/russia/world-cup')
            ->build();

        $tokens = $request->pathMatchesPattern('/world/{country}/world-cup');
        Assert::assertEquals(['country' => 'russia'], $tokens);
    }

    public function testIfMatch()
    {
        $cache = new VoidCachePool();

        $entity1 = new JsonEntity('abc');
        $entity2 = new JsonEntity('def');

        $request = Request::builder()
            ->withHeader('If-Match', $entity1->load($cache)->tag())
            ->build();

        Assert::assertTrue($request->preconditionFulfilled($entity1, $cache));
        Assert::assertFalse($request->preconditionFulfilled($entity2, $cache));
    }

    public function testIfNoneMatch()
    {
        $cache = new VoidCachePool();

        $entity1 = new JsonEntity('abc');
        $entity2 = new JsonEntity('def');

        $request = Request::builder()
            ->withHeader('If-None-Match', $entity1->load($cache)->tag())
            ->build();

        Assert::assertFalse($request->preconditionFulfilled($entity1, $cache));
        Assert::assertTrue($request->preconditionFulfilled($entity2, $cache));
    }

    public function testIfModifiedSince()
    {
        $cache = new ArrayCachePool();

        $entity = new JsonEntity('abc');
        $modified = $entity->load($cache)->modified();

        $request1 = Request::builder()
            ->withHeader('If-Modified-Since', gmdate('D, d M Y H:i:s', $modified - 10) . ' GMT')
            ->build();

        Assert::assertTrue($request1->preconditionFulfilled($entity, $cache));

        $request2 = Request::builder()
            ->withHeader('If-Modified-Since', gmdate('D, d M Y H:i:s', $modified + 10) . ' GMT')
            ->build();

        Assert::assertFalse($request2->preconditionFulfilled($entity, $cache));
    }

    public function testIfUnmodifiedSince()
    {
        $cache = new ArrayCachePool();

        $entity = new JsonEntity('abc');
        $modified = $entity->load($cache)->modified();

        $request1 = Request::builder()
            ->withHeader('If-Unmodified-Since', gmdate('D, d M Y H:i:s', $modified + 10) . ' GMT')
            ->build();

        Assert::assertTrue($request1->preconditionFulfilled($entity, $cache));

        $request2 = Request::builder()
            ->withHeader('If-Unmodified-Since', gmdate('D, d M Y H:i:s', $modified - 10) . ' GMT')
            ->build();

        Assert::assertFalse($request2->preconditionFulfilled($entity, $cache));
    }

    public function testAcceptParser()
    {
        $request1 = Request::builder()
            ->withHeader('Accept-Language', 'en-US,en;q=0.8,uk;q=0.6,ru;q=0.4')
            ->build();
        Assert::assertEquals(['en-US', 'en', 'uk', 'ru'], $request1->languageCodes());

        $request2 = Request::builder()
            ->withHeader('Accept-Language', 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5')
            ->build();
        Assert::assertEquals(['fr-CH', 'fr', 'en', 'de', '*'], $request2->languageCodes());
    }
}
