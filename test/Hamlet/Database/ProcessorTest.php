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
                ->group('phones', Processor::fieldExtractor('phone'))
                ->collect();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testFieldMapper() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::tailMapper([
                    'phone' => 'phoneNumber'
                ]))
                ->collect();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }

        public function testGroupFieldsExtractor() {
            $collection = Processor::with($this -> phones())
                ->group('phones', Processor::groupFieldsExtractor('name'))
                ->collect();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }
    }
}