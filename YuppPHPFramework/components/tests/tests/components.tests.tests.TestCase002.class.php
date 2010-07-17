<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.002', 'Cara');
YuppLoader::load('tests.model.002', 'Nariz');

class TestCase002 extends TestCase {

   private $bot;

   public function run()
   {
      $this->test1();
      $this->test2();
      $this->test3();
   }
   
   public function test1()
   {
      $cara = new Cara(
        array(
          "color" => "verde", // color incorrecto
          "nariz" => new Nariz(
            array(
              "tamanio"=>"enorme" // tamanio incorrecto
            )
          ) 
        )
      );
      
      // Debe fallar el save porque hay datos erroneos
      $this->assert( !$cara->save(), 'Test guardar cara '. print_r($cara->getErrors(), true));
      
      /*
      // Con errores en cara no llega a verificar errores en nariz
      print_r($cara->getNariz()->getErrors());
      
      // La nariz tambien debe tener errores
      $this->assert( count($cara->getNariz()->getErrors())>0, 'Test errores en nariz');
      */
   }
   
   public function test2()
   {
      $cara = new Cara(
        array(
          "color" => "blanco",
          "nariz" => new Nariz(
            array(
              "tamanio"=>"mediana"
            )
          ) 
        )
      );
      
      // Debe guardar bien porque los datos son correctos
      $this->assert( $cara->save(), 'Test guardar cara 2 '. print_r($cara->getErrors(), true));
      
      // La nariz NO debe tener errores
      $this->assert( count($cara->getNariz()->getErrors()) == 0, 'Test errores en nariz 2');
   }
   
   public function test3()
   {
      $cara = new Cara(
        array(
          "color" => "negro",
          "nariz" => new Nariz(
            array(
              "tamanio"=>"enorme" // tamanio incorrecto
            )
          ) 
        )
      );
      
      // Debe fallar el save porque hay datos erroneos
      $this->assert( !$cara->save(), 'Test guardar cara 3 '. print_r($cara->getErrors(), true));
      
      
      // FIXME
      // Sin errores en cara igual no llega a verificar errores en nariz!!!!
      //print_r($cara->getNariz()->getErrors());
      
      echo 'Test errores en nariz 3 '. print_r($cara->getNariz()->getErrors(), true).'<br/>';
      
      // La nariz tambien debe tener errores
      $this->assert( count($cara->getNariz()->getErrors())>0, 'Test errores en nariz 3 '. print_r($cara->getNariz()->getErrors(), true));
   }
   
   public function test4()
   {
      $c = Cara::count();
      
      $this->assert( $c == 1, 'Test hay una cara ['.$c.']');
   }
}

?>