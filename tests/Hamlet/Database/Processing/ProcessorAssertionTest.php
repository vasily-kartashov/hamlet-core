<?php

namespace Hamlet\Database\Processing;

use AssertionError;
use DateTime;
use Exception;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function Hamlet\Cast\_class;
use function Hamlet\Cast\_literal;
use function Hamlet\Cast\_mixed;
use function Hamlet\Cast\_union;
use RuntimeException;

class ProcessorAssertionTest extends TestCase
{
    protected function collector()
    {
        try {
            return new Collector([
                1 => new DateTime('@' . 1),
                2 => new DateTime('@' . 2),
                3 => "no"
            ]);
        } catch (Exception $e) {
            throw new RuntimeException('Unexpected exception', 0, $e);
        }
    }
    public function testInvalidTypeThrowsException()
    {
        ini_set('assert.exception', '1');

        $errorThrown = false;
        try {
            $this->collector()
                ->assertType(_mixed(), _class(DateTime::class))
                ->collectAll();
        } catch (AssertionError $e) {
            $errorThrown = true;
        }
        Assert::assertTrue($errorThrown);
    }

    public function testValidTypeThrowsNoException()
    {
        $this->collector()
            ->assertType(_mixed(), _union(_literal('no'), _class(DateTime::class)))
            ->collectAll();
        Assert::assertTrue(true);
    }

    public function testValidatorIsCalled()
    {
        $validatorCalled = false;
        $this->collector()
            ->assertForEach(function ($key, $value) use (&$validatorCalled) {
                $validatorCalled = true;
                return $value == 'no' || (($value instanceof DateTime) && $key == $value->getTimestamp());
            })
            ->collectAll();
        Assert::assertTrue($validatorCalled);
    }

    public function testValidatorRaisesAssertionErrorOnFailure()
    {
        ini_set('assert.exception', '1');

        $errorThrown = false;
        try {
            $this->collector()
                ->assertForEach(function ($_, $value) use (&$validatorCalled) {
                    return is_string($value);
                })
                ->collectAll();
        } catch (AssertionError $exception) {
            $errorThrown = true;
        }
        Assert::assertTrue($errorThrown);
    }
}
