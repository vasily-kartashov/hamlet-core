<?php

namespace Hamlet\Database {

    use UnitTestCase;

    class ProcessorTest extends UnitTestCase {

        public function testGroup() {
            $rows = [
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
            $collection = Processor::with($rows)->group('phones', function($row) {
                $phone = $row['phone'];
                unset($row['phone']);
                return [$phone, $row];
            })->collect();
            print_r($collection);
            $this->assertEqual(2, count($collection));
        }
    }
}