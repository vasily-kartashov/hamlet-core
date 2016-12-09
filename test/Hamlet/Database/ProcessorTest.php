<?php

namespace Hamlet\Database {

    use UnitTestCase;

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
            $this->assertEqual(1, count($collection));
            $this->assertEqual(2, count($collection['John']));
        }

        public function testWrapper() {
            $collection = Processor::with($this -> addresses())
                ->wrap('address', Processor::varyingExtractorByPrefix('address_'))
                ->collectToList();
            print_r($collection);
        }
    }
}