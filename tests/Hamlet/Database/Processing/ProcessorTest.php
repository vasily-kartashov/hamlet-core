<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Entity;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class Phone
{
    /** @var string */
    public $name;

    /** @var string */
    public $phone;
}

class Address
{
    /** @var string */
    public $street;

    /** @var int */
    public $number;
}

class AddressBookEntry implements Entity
{
    /** @var string */
    public $name;

    /** @var Address[] */
    public $addresses;
}

class PhoneEntity implements Entity
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $phone;

    public function name(): string
    {
        return $this->name;
    }

    public function phone(): string
    {
        return $this->phone;
    }
}

class ProcessorTest extends TestCase
{

    private function phones()
    {
        return [
            [
                'name' => 'John',
                'phone' => '123'
            ],
            [
                'name' => 'John',
                'phone' => '785'
            ],
            [
                'name' => 'Bill',
                'phone' => '12333'
            ]
        ];
    }

    private function addresses()
    {
        return [
            [
                'name' => 'John',
                'address_street' => 'Lenin Street',
                'address_number' => 1917
            ],
            [
                'name' => 'John',
                'address_street' => 'Pushkin Square',
                'address_number' => 1
            ],
            [
                'name' => 'John',
                'address_street' => null,
                'address_number' => null
            ],
            [
                'name' => 'Anatoly',
                'address_street' => 'Tolstoy lane',
                'address_number' => 1812
            ]
        ];
    }

    private function cities()
    {
        return [
            [
                'country' => 'Australia',
                'state' => 'Victoria',
                'city' => 'Geelong'
            ],
            [
                'country' => 'Australia',
                'state' => 'Victoria',
                'city' => 'Melbourne'
            ],
            [
                'country' => 'Russia',
                'state' => 'Saratovskaya Oblast',
                'city' => 'Balakovo'
            ],
            [
                'country' => 'Russia',
                'state' => 'Saratovskaya Oblast',
                'city' => 'Saratov'
            ]
        ];
    }

    private function locations()
    {
        return [
            [
                'country' => null,
                'state' => 'Victoria',
                'city' => 'Geelong'
            ],
            [
                'country' => 'Australia',
                'state' => 'Victoria',
                'city' => 'Melbourne'
            ],
            [
                'country' => 'Russia',
                'state' => 'Saratovskaya Oblast',
                'city' => 'Balakovo'
            ],
            [
                'country' => null,
                'state' => null,
                'city' => 'Saratov'
            ]
        ];
    }

    public function testFieldExtractor()
    {
        $collection = (new Selector($this->phones()))
            ->selectValue('phone')->groupInto('phones')
            ->collectAll();

        Assert::assertEquals(2, count($collection));
        Assert::assertEquals('John', $collection[0]['name']);
        Assert::assertEquals(2, count($collection[0]['phones']));
    }

    public function testCollectToMap()
    {
        $collection = (new Selector($this->phones()))
            ->selectValue('phone')->groupInto('phones')
            ->map('name', 'phones')->flatten()
            ->collectAll();

        Assert::assertEquals(2, count($collection));
        Assert::assertArrayHasKey('John', $collection);
        Assert::assertArrayHasKey('Bill', $collection);
    }

    public function testPrefixExtractor()
    {
        $collection = (new Selector($this->addresses()))
            ->selectByPrefix('address_')->groupInto('addresses')
            ->map('name', 'addresses')->flatten()
            ->collectAll();

        Assert::assertEquals(2, count($collection));
        Assert::assertArrayHasKey('John', $collection);
        Assert::assertEquals(1812, $collection['Anatoly'][0]['number']);
    }

    public function testNestedGroups()
    {
        $collection = (new Selector($this->cities()))
            ->selectValue('city')->groupInto('cities')
            ->map('state', 'cities')->flattenInto('states')
            ->map('country', 'states')->flatten()
            ->collectAll();

        Assert::assertEquals('Balakovo', $collection['Russia']['Saratovskaya Oblast'][0]);
    }

    public function testCollectTypedList()
    {
        $collection = (new Selector($this->phones()))
            ->selectAll()->cast(Phone::class)
            ->collectAll();

        Assert::assertInstanceOf(Phone::class, $collection[0]);
    }

    public function testCollectTypedListOfMappedEntities()
    {
        $collection = (new Selector($this->phones()))
            ->selectAll()->cast(PhoneEntity::class)
            ->collectAll();

        Assert::assertInstanceOf(PhoneEntity::class, $collection[0]);
    }

    public function testCollectNestedTypedList()
    {
        $collection = (new Selector($this->addresses()))
            ->selectByPrefix('address_')->castInto(Address::class, 'address')
            ->selectValue('address')->groupInto('addresses')
            ->selectAll()->cast(AddressBookEntry::class)
            ->collectAll();

        Assert::assertInstanceOf(AddressBookEntry::class, $collection[0]);
        Assert::assertInstanceOf(Address::class, $collection[0]->addresses[1]);
    }

    public function testCollate()
    {
        $collection = (new Selector($this->locations()))
            ->collateAll()
            ->collectAll();

        Assert::assertEquals('Victoria', $collection[0]);
        Assert::assertEquals('Australia', $collection[1]);
        Assert::assertEquals('Russia', $collection[2]);
        Assert::assertEquals('Saratov', $collection[3]);
    }

    public function testCollator()
    {
        $collection = (new Selector($this->locations()))
            ->collate('state', 'city')->name('details')
            ->map('country', 'details')->flatten()
            ->collectAll();

        Assert::assertEquals('Saratovskaya Oblast', $collection['Russia']);
    }
}
