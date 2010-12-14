<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.003', 'Entidad');
YuppLoader::load('tests.model.003', 'TestPersona');

class TestCase003 extends TestCase {

   public function run()
   {
      $this->test1();
      $this->test2();
   }
   
   public function test1()
   {
      $dal = new DAL('tests');
      
      $this->assert( $dal->tableExists( YuppConventions::tableName('Entidad') ), 'Test 003.1.1 existe tabla');

      $this->assert( $dal->tableExists( YuppConventions::tableName('TestPersona') ), 'Test 003.1.2 existe tabla');
   }
   
   public function test2()
   {
       $p = new TestPersona(array(
         'tipo' => 'abcd',
         'nombre' => 'Carlos',
         'edad' => 4,
         'num' => 33
       ));
       
       $this->assert( !$p->validate(), 'Test 003.2.1 validate: '. print_r($p->getErrors(), true));
   }
}

?>