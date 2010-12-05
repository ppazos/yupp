<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.dirtyBit', 'Class1');
YuppLoader::load('tests.model.dirtyBit', 'Class2');
YuppLoader::load('tests.model.dirtyBit', 'Class3');

class TestDirtyBit extends TestCase {

   public function run()
   {
      $this->test1();
      $this->test2();
      
      //$this->reset();
   }
   
   public function test1()
   {
      $c1 = new Class1( array(
        "attr11" => "un texto libre",
        "attr12" => date("Y-m-d"),
        "attr13" => 12345,
        "class2" => new Class2( array(
          "attr21" => "otro texto libre",
          "attr22" => date("Y-m-d"),
          "attr23" => 34363,
          "class3" => new Class3( array(
            "attr31" => "un ultimo texto libre",
            "attr32" => date("Y-m-d"),
            "attr33" => 83715
          ) ) // class3
        ) ) // class2
      ) ); // class1
      
      // Debe fallar el save porque hay datos erroneos
      $this->assert( $c1->save(), 'Test guardar class1'. print_r($c1->getErrors(), true));
   }
   
   // Test actualizar sin considerar dirtyBit
   public function test2()
   {
      //Logger::getInstance()->on();
      
      //$timer = new Timer();
      //$timer->start();
       
      $c1 = Class1::get(1);      
      $c2 = $c1->getClass2();
      $c3 = $c2->getClass3();
      $c3->setAttr31('Un texto distinto al anterior');
      
      $this->assert( $c1->save(), 'Test guardar 1 '. print_r($c1->getErrors(), true));
      
      //$timer->stop();
      //echo 'tiempo total 1: ' . $timer->getElapsedTime() . '<br/>';
      
      
      // Intento guardar de nuevo la misma instancia, no deberia guardar porque ya se guardo y esta limpia.
      // Observar que este save no hace consultas a la base, y por lo tanto, es mucho mas rapido evitando updates innecesarios.
      //$timer->reset();
      //$timer->start();
      
      $this->assert( $c1->save(), 'Test guardar 2 '. print_r($c1->getErrors(), true));
      
      //$timer->stop();
      //echo 'tiempo total 2: ' . $timer->getElapsedTime() . '<br/>';
      
      //Logger::getInstance()->off();
      
      // La nariz NO debe tener errores
      //$this->assert( count($cara->getNariz()->getErrors()) == 0, 'Test errores en nariz 2');
   }

   // Borra lo creado
   public function reset()
   {
      $c1 = Class1::get(1);
      $c2 = $c1->getClass2();
      $c3 = $c2->getClass3();
      
      $c1->delete(true);
      $c2->delete(true);
      $c3->delete(true);
   }
}

?>