<?php

YuppLoader::load('tests.model.002', 'Cara');
YuppLoader::load('tests.model.002', 'Nariz');
YuppLoader::load('core.persistent.serialize', 'JSONPO');

class ExtraTestsController extends YuppController {

   function inListAction()
   {
      Logger::getInstance()->on();
      
      $cara = new Cara(
        array(
          "color" => NULL,
          "nariz" => new Nariz(
            array(
              "tamanio"=>"fghfghfhg"
            )
          ) 
        )
      );

      /*
      try
      {
          if (!$cara->validate(true)) echo 'error<br/>';
          else echo 'ok<br/>';
          
          echo 'try: <br/>';
          //print_r($cara->getErrors());
      }
      catch(Exception $e)
      {
          echo 'except: <br/>';
          print_r($e->getMessage());
      }
      */
      
      
      if ( !$cara->save() )
      {
         echo 'Test guardar cara 5 errores de cara '. print_r($cara->getErrors(), true);
         echo 'Test guardar cara 5 errores de nariz'. print_r($cara->getNariz()->getErrors(), true);
      }
      
      print_r(JSONPO::toJSON($cara, true));
      
      //print_r($cara->getErrors());
      //print_r($cara->getNariz()->getErrors());
      
      Logger::getInstance()->off();
      
      return $this->renderString('fin');
   }
}
?>