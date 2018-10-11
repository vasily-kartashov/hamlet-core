<?php

namespace Hamlet\Requests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class RequestUtilsTest extends TestCase
{
    public function testGetLanguageCodes()
    {
        $request1 = Request::empty()
            ->withHeader('Accept-Language', 'en-US,en;q=0.8,uk;q=0.6,ru;q=0.4');
        Assert::assertEquals(['en-US', 'en', 'uk', 'ru'], RequestUtils::getLanguageCodes($request1));

        $request2 = Request::empty()
            ->withHeader('Accept-Language', 'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5');
        Assert::assertEquals(['fr-CH', 'fr', 'en', 'de', '*'], RequestUtils::getLanguageCodes($request2));
    }
}
