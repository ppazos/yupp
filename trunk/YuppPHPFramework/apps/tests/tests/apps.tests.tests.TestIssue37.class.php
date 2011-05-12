<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.issue37', 'AAA');
YuppLoader::load('tests.model.issue37', 'BBB');
YuppLoader::load('tests.model.issue37', 'CCC');

class TestIssue37 extends TestCase {

   public function run()
   {
      $this->test1();
      //$this->test2();
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
   
   public function test2()
   {
      //$bot2 = Botella::get( $this->bot->getId() );
      //$bot2 = Botella::get( $bot->getId() ); // si descomento obtengo un fatal error, deberia reportarlo en la suite...
      
      //$this->assert( !is_null($bot2), 'Test carga por id');
      
      //$this->assert( is_bool($bot2->getTapaRosca()), 'Test boolean 3');
   }
   
}

?>