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

        public function testFieldExtractor() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::varyingAtomicExtractor('phone'))
                ->collectToList();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testFieldMapper() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::varyingExtractor([
                    'phone' => 'phoneNumber'
                ]))
                ->collectToList();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testGroupFieldsExtractor() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::commonExtractor(['name']))
                ->collectToList();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testCollectToMap() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::varyingAtomicExtractor('phone'))
                ->collectToMap('name', 'phones');
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }
    }
}