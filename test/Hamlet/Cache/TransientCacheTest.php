<?php

namespace Hamlet\Cache;

use UnitTestCase;

class TransientCacheTest extends UnitTestCase
{
    public function testCache()
    {
        $cache = new TransientCache();

        list($value, $found) = $cache->get('key0');
        $this->assertNull($value);
        $this->assertFalse($found);

        list($value, $found) = $cache->get('key1', 'value1');
        $this->assertEqual($value, 'value1');
        $this->assertFalse($found);

        $cache->set('key2', 'value2');
        list($value, $found) = $cache->get('key2');
        $this->assertEqual($value, 'value2');
        $this->assertTrue($found);

        $cache->set('key3', 'value3', 2);
        sleep(1);
        list($value, $found) = $cache->get('key3');
        $this->assertEqual($value, 'value3');
        $this->assertTrue($found);
        sleep(2);
        list($value, $found) = $cache->get('key3');
        $this->assertNull($value);
        $this->assertFalse($found);
    }
}