<?php

namespace Hamlet\Database {

    use UnitTestCase;

    class Phone {
        public $name, $phone;
    }

    class Address {
        /** @var string */
        public $street;
        /** @var int */
        public $number;
    }

    class AddressBookEntry {
        /** @var string */
        public $name;
        /** @var Address[] */
        public $addresses;
    }

    class PhoneEntity extends MappedEntity {

        private $name, $phone;

        public function name() : string {
            return $this->name;
        }

        public function phone() : string {
            return $this->phone;
        }
    }

    class ProcessorTest extends UnitTestCase {

        private function phones() {
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

        private function addresses() {
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

        private function cities() {
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

        private function locations() {
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

        public function testFieldExtractor() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::varyingAtomicExtractor('phone'))
                ->collectToList();
            // print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testFieldMapper() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::varyingExtractor([
                    'phone' => 'phoneNumber'
                ]))
                ->collectToList();
            // print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testGroupFieldsExtractor() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::commonExtractor(['name']))
                ->collectToList();
            // print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testCollectToMap() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::varyingAtomicExtractor('phone'))
                ->collectToMap('name', 'phones');
            // print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testPrefixExtractor() {
            $collection = Processor::with($this -> addresses())
                ->group('addresses', Processor::varyingExtractorByPrefix('address_'))
                ->collectToMap('name', 'addresses');
            // print_r($collection);
            $this->assertEqual(2, count($collection));
            $this->assertEqual(2, count($collection['John']));
        }

        public function testWrapper() {
            $collection = Processor::with($this -> addresses())
                ->wrap('address', Processor::varyingExtractorByPrefix('address_'))
                ->collectToList();
            //print_r($collection);
        }

        public function testMap() {
            $collection = Processor::with($this -> addresses())
                ->map('address_street', 'strtoupper')
                ->collectToList();
            // print_r($collection);
            $this->assertEqual('LENIN STREET', $collection[0]['address_street']);
        }

        public function testNestedGroups() {
            $collection = Processor:: with($this -> cities())
                ->group('cities', Processor::varyingAtomicExtractor('city'))
                ->group('states', Processor::mapExtractor('state', 'cities'))
                ->collectToMap('country', 'states');
            // print_r($collection);
            $this->assertEqual('Balakovo', $collection['Russia']['Saratovskaya Oblast'][0]);
        }

        public function testCollectTypedList() {
            /** @var Phone[] $collection */
            $collection = Processor::with($this -> phones())
                ->collectToList(Phone::class);
            $this->assertIsA($collection[0], Phone::class);
        }

        public function testCollectTypedListOfMappedEntities() {
            /** @var Phone[] $collection */
            $collection = Processor::with($this -> phones())
                ->collectToList(PhoneEntity::class);
            $this->assertIsA($collection[0], PhoneEntity::class);
        }

        public function testCollectNestedTypedList() {
            /** @var AddressBookEntry[] $collection */
            $collection = Processor::with($this -> addresses())
                ->group('addresses', Processor::varyingExtractorByPrefix('address_'), Address::class)
                ->collectToList(AddressBookEntry::class);
            $this->assertIsA($collection[0], AddressBookEntry::class);
            $this->assertIsA($collection[0]->addresses[1], Address::class);
        }

        public function testCollate() {
            $collection = Processor::with($this -> locations())
                ->collate()
                ->collectToList();
            $this->assertEqual('Victoria', $collection[0]);
            $this->assertEqual('Australia', $collection[1]);
            $this->assertEqual('Russia', $collection[2]);
            $this->assertEqual('Saratov', $collection[3]);
        }

        public function testCollator()
        {
            $collection = Processor::with($this -> locations())
                ->wrap('details', Processor::collator('state', 'city'))
                ->collectToMap('country', 'details');
            $this->assertEqual('Saratovskaya Oblast', $collection['Russia']);
        }
    }
}