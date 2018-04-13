<?php

namespace Hamlet\Database\SQLite;

use Hamlet\Database\Database;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class SQLiteDatabaseTest extends TestCase
{
    /** @var Database */
    private $database;

    public function setUp()
    {
        $this->database = Database::sqlite3(tempnam(sys_get_temp_dir(), '.sqlite'));
        $this->database->prepare('
            CREATE TABLE users (
              id INTEGER,
              name VARCHAR(255)
            )
        ')->execute();
        $this->database->prepare('
            CREATE TABLE addresses (
              user_id INTEGER,
              address VARCHAR(255)
            ) 
        ')->execute();
    }

    public function testInsertAndSelect()
    {
        $this->database->prepare("INSERT INTO users VALUES (1, 'Vladimir')")->execute();
        $this->database->prepare("INSERT INTO addresses VALUES (1, 'Moskva')")->execute();
        $this->database->prepare("INSERT INTO addresses VALUES (1, 'Vladivostok')")->execute();

        $procedure = $this->database->prepare('
            SELECT id,
                   name,
                   address
              FROM users 
                   JOIN addresses
                     ON users.id = addresses.user_id      
        ');
        $result = $procedure->processAll()
            ->selectValue('address')->groupInto('addresses')
            ->selectFields('name', 'addresses')->name('user')
            ->map('id', 'user')->flatten()
            ->collectAll();

        Assert::assertCount(1, $result);
        Assert::assertArrayHasKey(1, $result);
        Assert::assertEquals('Vladimir', $result[1]['name']);
        Assert::assertCount(2, $result[1]['addresses']);

        $procedure->stream()
            ->selectValue('address')->groupInto('addresses')
            ->selectFields('name', 'addresses')->name('user')
            ->map('id', 'user')->flatten()
            ->forEachWithIndex(function ($id, $user) {
                Assert::assertEquals(1, $id);
                Assert::assertCount(2, $user['addresses']);
            });
    }
}
