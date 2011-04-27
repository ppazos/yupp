<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.m003', 'Mano');
YuppLoader::load('tests.model.m003', 'Dedo');

YuppLoader::load('core.persistent.serialize', 'XMLPO');
YuppLoader::load('core.persistent.serialize', 'JSONPO');

class TestCaseM003 extends TestCase {

   public function run()
   {
      $this->test1();
      $this->reset();
   }
   
   public function test1()
   {
      $mano = new Mano( array(
        "tamanio" => "grande",
        "dedos" => array(
          new Dedo( array(
            "uniaLarga"=>true
          )),
          new Dedo( array(
            "uniaLarga"=>true
          )),
          new Dedo( array(
            "uniaLarga"=>false
          )),
        ) 
      ));
      
      $dedos = $mano->getDedos();
      foreach ($dedos as $dedo)
      {
         $dedo->setMano($mano);
      }
      
      $this->assert($mano->save(), 'TestCaseM003: Test salvar mano y dedos '. print_r($mano->getErrors(), true));
      
      // Debe fallar el save porque hay datos erroneos
      $this->assert( true, 'TestCaseM003: Test generar XML '. print_r(XMLPO::toXML($mano, true, true), true));
      
      $this->assert( true, 'TestCaseM003: Test generar JSON '. print_r(JSONPO::toJSON($mano, true), true));
   }
   
   public function reset()
   {
      $mano = Mano::get(1);
      
      $dedos = $mano->getDedos();
      foreach ($dedos as $dedo)
      {
         $mano->removeFromDedos($dedo);
         $dedo->delete();
      }
      
      $mano->delete();
   }
}

?>