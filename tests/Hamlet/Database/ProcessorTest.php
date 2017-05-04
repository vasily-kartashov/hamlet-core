<?php

namespace Hamlet\Database;

use Hamlet\Database\Processing\Processor;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class Phone
{
    public $name, $phone;
}

class Address
{
    /** @var string */
    public $street;
    /** @var int */
    public $number;
}

class AddressBookEntry
{
    /** @var string */
    public $name;
    /** @var Address[] */
    public $addresses;
}

class PhoneEntity implements Entity
{
    private $name, $phone;

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
        $collection = Processor::with($this->phones())
            ->pickOne('phone')->asList('phones')
            ->collectToList();
        Assert::assertEquals(2, count($collection));
        Assert::assertEquals('John', $collection[0]['name']);
        Assert::assertEquals(2, count($collection[0]['phones']));
    }

    public function testCollectToMap()
    {
        $collection = Processor::with($this->phones())
            ->pickOne('phone')->asList('phones')
            ->map('name', 'phones')
            ->collectToFlattenMap();
        Assert::assertEquals(2, count($collection));
        Assert::assertArrayHasKey('John', $collection);
        Assert::assertArrayHasKey('Bill', $collection);
    }

    public function testPrefixExtractor()
    {
        $collection = Processor::with($this->addresses())
            ->pickByPrefix('address')->asList('addresses')
            ->map('name', 'addresses')
            ->collectToFlattenMap();
        Assert::assertEquals(2, count($collection));
        Assert::assertArrayHasKey('John', $collection);
        Assert::assertEquals(1812, $collection['Anatoly'][0]['number']);
    }

    public function testNestedGroups()
    {
        $collection = Processor:: with($this->cities())
            ->pickOne('city')->asList('cities')
            ->map('state', 'cities')->asFlattenMap('states')
            ->map('country', 'states')
            ->collectToFlattenMap();
        Assert::assertEquals('Balakovo', $collection['Russia']['Saratovskaya Oblast'][0]);
    }

    public function testCollectTypedList()
    {
        $collection = Processor::with($this->phones())
            ->all()->asObject('phone', Phone::class)
            ->unwrapAndCollectToList();
        Assert::assertInstanceOf(Phone::class, $collection[0]);
    }

    public function testCollectTypedListOfMappedEntities()
    {
        $collection = Processor::with($this->phones())
            ->all()->asObject('phone', PhoneEntity::class)
            ->unwrapAndCollectToList();
        Assert::assertInstanceOf(PhoneEntity::class, $collection[0]);
    }

    public function testCollectNestedTypedList()
    {
        $collection = Processor::with($this->addresses())
            ->pickByPrefix('address')->asObject('address', Address::class)
            ->pickOne('address')->asList('addresses')
            ->all()->asObject('entry', AddressBookEntry::class)
            ->unwrapAndCollectToList();
        Assert::assertInstanceOf(AddressBookEntry::class, $collection[0]);
        Assert::assertInstanceOf(Address::class, $collection[0]->addresses[1]);
    }

    public function testCollate()
    {
        $collection = Processor::with($this->locations())
            ->collateAll()
            ->collectToList();
        Assert::assertEquals('Victoria', $collection[0]);
        Assert::assertEquals('Australia', $collection[1]);
        Assert::assertEquals('Russia', $collection[2]);
        Assert::assertEquals('Saratov', $collection[3]);
    }

    public function testCollator()
    {
        $collection = Processor::with($this->locations())
            ->collate('state', 'city')->asField('details')
            ->map('country', 'details')
            ->collectToFlattenMap();
        Assert::assertEquals('Saratovskaya Oblast', $collection['Russia']);
    }
}
