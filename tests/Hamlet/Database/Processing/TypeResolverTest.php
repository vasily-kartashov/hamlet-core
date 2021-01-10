<?php

namespace Hamlet\Database\Processing;


use Hamlet\Database\Entity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AbstractUser implements Entity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var float|null
     */
    protected $latitude;

    /**
     * @var float|null
     */
    protected $longitude;

    public static function __resolveType(array $properties)
    {
        if (isset($properties['latitude']) && isset($properties['longitude'])) {
            return User::class;
        } else {
            return AnonymousUser::class;
        }
    }

    public function name()
    {
        return $this->name;
    }
}

class User extends AbstractUser
{
    public function latitude()
    {
        return $this->latitude;
    }

    public function longitude()
    {
        return $this->longitude;
    }
}

class AnonymousUser extends AbstractUser
{
}

class SuperAnonymousUser extends AnonymousUser
{
}

class RandomClass implements Entity
{
}

class TypeResolverTest extends TestCase
{
    private function users()
    {
        return [
            [
                'name' => 'Pyotr',
                'latitude' => 12.03,
                'longitude' => -33.9
            ],
            [
                'name' => 'Anfeesa',
                'latitude' => -100.03,
                'longitude' => 19.001
            ],
            [
                'name' => 'Mikhail',
                'latitude' => null,
                'longitude' => null
            ],
            [
                'name' => 'Lena',
                'latitude' => 24.12,
                'longitude' => -13.32
            ],

        ];
    }

    public function testTypeResolver()
    {
        $collection = (new Selector($this->users()))
            ->selectAll()->cast(AbstractUser::class)
            ->collectAll();

        Assert::assertInstanceOf(User::class, $collection[0]);
        Assert::assertInstanceOf(User::class, $collection[1]);
        Assert::assertInstanceOf(AnonymousUser::class, $collection[2]);
        Assert::assertInstanceOf(User::class, $collection[3]);
    }

    public function testTypeResolverThrowsException()
    {
        $this->expectException(RuntimeException::class);
        (new Selector($this->users()))
            ->selectAll()->cast(SuperAnonymousUser::class)
            ->collectAll();
    }

    public function testTypeResolverThrowsExceptionOfUnrelatedClass()
    {
        $this->expectException(RuntimeException::class);
        (new Selector($this->users()))
            ->selectAll()->cast(RandomClass::class)
            ->collectAll();
    }
}
