<?php

namespace Hamlet\Database\SQLite;

use Hamlet\Database\Database;
use Hamlet\Database\Entity;
use PHPUnit\Framework\TestCase;

class SQLiteJsonMappingTest extends TestCase
{
    /**
     * @var Database
     */
    private $database;

    public function setUp(): void
    {
        $this->database = Database::sqlite3(tempnam(sys_get_temp_dir(), '.sqlite'));
        $this->database->prepare('
            CREATE TABLE users (
              id INTEGER,
              name VARCHAR(255),
              preferences TEXT
            )
        ')->execute();
        $this->database->prepare("
            INSERT INTO users (id, name, preferences) VALUES (1, 'John', '{\"newsletter\": true, \"age\": 18}')
        ")->execute();
    }

    public function testJsonMapping(): void
    {
        $procedure = $this->database->prepare('SELECT * FROM users');
        $users = $procedure->processAll()
            ->selectValue('preferences')->castInto(UserPreferences2::class, 'preferences', true)
            ->selectAll()->cast(User2::class)->collectAll();

        $this->assertCount(1, $users);
        $this->assertInstanceOf(UserPreferences2::class, $users[0]->preferences());
        $this->assertTrue($users[0]->preferences()->newsletter());
        $this->assertEquals(18, $users[0]->preferences()->age());
    }
}

class User2 implements Entity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var UserPreferences2
     */
    private $preferences;

    public function __construct(int $id, string $name, UserPreferences2 $preferences)
    {
        $this->id = $id;
        $this->name = $name;
        $this->preferences = $preferences;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function preferences(): UserPreferences2
    {
        return $this->preferences;
    }
}

class UserPreferences2 implements Entity
{
    /**
     * @var bool
     */
    private $newsletter;

    /**
     * @var int
     */
    private $age;

    public function __construct(bool $newsletter, int $age)
    {
        $this->newsletter = $newsletter;
        $this->age = $age;
    }

    public function newsletter(): bool
    {
        return $this->newsletter;
    }

    public function age(): int
    {
        return $this->age;
    }
}
