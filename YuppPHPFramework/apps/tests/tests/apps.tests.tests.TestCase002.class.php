<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.002', 'Cara');
YuppLoader::load('tests.model.002', 'Nariz');

class TestCase002 extends TestCase {

   //private $bot;

   public function run()
   {
      $this->test1();
      $this->test2();
      $this->test3();
      $this->test5();
      
      //$this->testXML();
      
      //$this->getByTest();
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

      //print_r($cara);      
      //echo 'Test errores en nariz 3 '. print_r($cara->getNariz()->getErrors(), true).'<br/>';
      
      // La nariz tambien debe tener errores
      $this->assert( count($cara->getNariz()->getErrors())>0, 'Test errores en nariz 3 '. print_r($cara->getNariz()->getErrors(), true));
   }
   
   public function test4()
   {
      $c = Cara::count();
      
      $this->assert( $c == 1, 'Test hay una cara ['.$c.']');
   }
   
   public function test5()
   {
      //Logger::getInstance()->on();
      
      $cara = new Cara(
        array(
          "color" => NULL,
          "nariz" => new Nariz(
            array(
              "tamanio"=>"chica"
            )
          ) 
        )
      );
      
      /*
      try {
          if (!$cara->validate(true)) echo 'error<br/>';
          else echo 'ok<br/>';
          
          echo 'try: <br/>';
          print_r($cara->getErrors());
          //print_r($cara->getNariz()->getErrors());
          
      } catch(Exception $e)
      {
          echo 'except: <br/>';
          print_r($e->getMessage());
      }
      */
      
      
      // Debe fallar el save porque hay datos erroneos
      $this->assert( $cara->save(), 'Test guardar cara 5 '. print_r($cara->getErrors(), true));
      
      //Logger::getInstance()->off();
   }
   
   public function testXML()
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
      
      YuppLoader::load('core.persistent.serialize', 'XMLPO');
      echo XMLPO::toXML($cara, true, true);
   }
   
   public function getByTest()
   {
      //Cara::getByColor('blanco');
   }
}

?>