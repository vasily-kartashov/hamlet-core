<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace Hamlet\Database;

use Exception;
use Phake;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseTest extends TestCase
{
    public function testTransactionRolledBackOnExceptionAndTheConnectionIsReturnedIntoPool()
    {
        $database = Phake::partialMock(Database::class);

        $exceptionThrown = false;
        try {
            $database->withTransaction(function () {
                throw new RuntimeException();
            });
        } catch (Exception $exception) {
            $exceptionThrown = true;
        }

        Assert::assertTrue($exceptionThrown);

        Phake::inOrder(
            Phake::verify($database)->startTransaction(),
            Phake::verify($database)->rollback()
        );
    }

    public function testNestedCallsReuseTransactions()
    {
        $database = Phake::partialMock(Database::class);

        $database->withTransaction(function () use ($database) {
            $database->withTransaction(function () use ($database) {
                $database->withTransaction(function () {});
            });
        });

        Phake::inOrder(
            Phake::verify($database)->startTransaction(),
            Phake::verify($database)->commit()
        );
    }

    public function testNestedCallsReuseFinals()
    {
        $callCounter = 0;
        $finally = [
            'counter' => function () use (&$callCounter) {
                $callCounter++;
            }
        ];

        $database = Phake::partialMock(Database::class);
        $database->withTransaction(function () use ($database, $finally, $callCounter) {
            $database->withTransaction(function () use ($database, $finally, $callCounter) {
                $database->withTransaction(function () {}, $finally);
                Assert::assertEquals(0, $callCounter);
            }, $finally);
            Assert::assertEquals(0, $callCounter);
        }, $finally);
        Assert::assertEquals(1, $callCounter);
    }

    public function testFinalsNotCalledOnException()
    {
        $callCounter = 0;
        $finally = [
            'counter' => function () use (&$callCounter) {
                $callCounter++;
            }
        ];

        $database = Phake::partialMock(Database::class);

        $exceptionThrown = false;
        try {
            $database->withTransaction(function () {
                throw new RuntimeException();
            }, $finally);
        } catch (Exception $exception) {
            $exceptionThrown = true;
        }

        Assert::assertTrue($exceptionThrown);
        Assert::assertEquals(0, $callCounter);
    }
}
