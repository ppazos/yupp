<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.issue37', 'AAA');
YuppLoader::load('tests.model.issue37', 'BBB');
YuppLoader::load('tests.model.issue37', 'CCC');

class TestIssue37 extends TestCase {

   public function run()
   {
      //$this->test1();
      $this->test2();
   }
   
   public function test1()
   {
      $a = new AAA();
      $c = new CCC(array(
        'attrBBB' => 'sfasdf',
        'attrCCC' => 'ghjghjg'
      ));
      
      $a->addToBs($c);
      
     
      $this->assert( $a->save(), 'Test guardar aaa '. print_r($a->getErrors(), true));
   }
   
   /**
    * test de rollback de toda la transaccion del save en cascada.
    */
   public function test2()
   {
      $a = new AAA();
      $c = new CCC(array(
        'attrBBB' => 'sfasdf',
        'attrCCC' => 'aa' // El save en cascada deberia fallar porque debe tener 5 caracteres o mas
      ));
      
      $a->addToBs($c);
      
     
      $this->assert( !$a->save(), 'Test rollback '. print_r($c->getErrors(), true) );
   }
   
}

?>