<?php

namespace Hamlet\Database {

    use UnitTestCase;

    class SQLiteProcedureTest extends UnitTestCase {

        private function initDatabase() {
            $database = Database::sqlite(':memory:');
            $query = "
                CREATE TABLE users ( 
                    name VARCHAR(255) 
                )
            ";
            $database -> prepare($query) -> execute();
            $query = "
                INSERT INTO users (name) 
                     VALUES ('john'),
                            ('bill'),
                            ('janet')
            ";
            $database -> prepare($query) -> execute();
            return $database;
        }

        public function testListBinding() {
            $database = $this -> initDatabase();
            $query = "
                SELECT *
                  FROM users
                 WHERE name IN ?
                   AND name != ?
            ";
            $procedure = $database -> prepare($query);
            $procedure -> bindStringList(['john', 'bill']);
            $procedure -> bindString('herman');
            print_r($procedure -> fetchAll());
        }
    }
}