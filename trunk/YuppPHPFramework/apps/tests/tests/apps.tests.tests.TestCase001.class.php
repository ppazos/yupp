<?php

YuppLoader::load('core.testing', 'TestCase');

YuppLoader::load('tests.model.001', 'Botella');

class TestCase001 extends TestCase {

   private $bot;

   public function run()
   {
      // Para ver logs y optimizar llamadas
      $this->testSaveSimple();
      
      /*
      $this->test1();
      $this->test2();
      $this->test3();
      $this->reset();
      */
   }
   
   public function testSaveSimple()
   {
      //Logger::getInstance()->on();
      
      YuppLoader::load('core.support', 'Timer');
      $timer = new Timer();
      $timer->start();
      
      $bot = new Botella(
        array(
          "material" => "vidrio",
          "capacidad" => 1.5, 
          "tapaRosca" => true
        )
      );
      
      $this->assert( $bot->save(), 'Test guardar 1 '. print_r($bot->getErrors(), true));
      
      $timer->stop();
      echo 'Time: '. $timer->getElapsedTime() .'<br/>';
      
      //Logger::getInstance()->off();
   }
   
   public function test1()
   {
      $bot = new Botella(
        array(
          "material" => "vidrio",
          "capacidad" => 1.5, 
          "tapaRosca" => true
        )
      );
      
      $this->assert( is_bool($bot->getTapaRosca()), 'Test boolean 1', array('getTapaRosca'=>$bot->getTapaRosca()));
      
      $this->assert( $bot->save(), 'Test guardar 1 '. print_r($bot->getErrors(), true));
      
      $this->assert( is_bool($bot->getTapaRosca()), 'Test boolean 2');
      
      // Asi el test2 puede accederla
      $this->bot = $bot;
   }
   
   public function test2()
   {
      $bot2 = Botella::get( $this->bot->getId() );
      //$bot2 = Botella::get( $bot->getId() ); // si descomento obtengo un fatal error, deberia reportarlo en la suite...
      
      $this->assert( !is_null($bot2), 'Test carga por id');
      
      $this->assert( is_bool($bot2->getTapaRosca()), 'Test boolean 3');
   }
   
   public function test3()
   {
      $c = Botella::count();
      
      $this->assert( $c == 1, 'Test hay una botella ['.$c.']');
   }
   
   public function reset()
   {
      $bot = Botella::get(1);
      $bot->delete();
   }
}

?>