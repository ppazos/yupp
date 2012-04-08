<?php

YuppLoader::load('tests.model.002', 'Cara');
YuppLoader::load('tests.model.002', 'Nariz');
YuppLoader::load('tests.model.a004', 'Pagina');
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
   
   function toJSONArrayAction()
   {
      $list = array(
        new Cara( array(
          "color" => "blanco",
          "nariz" => new Nariz( array(
            "tamanio"=>"chica"
          )) 
        )),
        new Cara( array(
          "color" => "negro",
          "nariz" => new Nariz( array(
            "tamanio"=>"mediana"
          )) 
        )),
        new Cara( array(
          "color" => "pardo",
          "nariz" => new Nariz( array(
            "tamanio"=>"grande"
          )) 
        ))
      );
      
      print_r( JSONPO::toJSONArray($list, true) );
      
      return $this->renderString('');
   }
   
   /**
    * Usa test a004.
    */
   function toJSONArrayHasManyAction()
   {
      $list = array(
        new Pagina( array(
          "titulo" => "blanco", "contenido" => "zzz xxx ccc",
          "subpages" => array(
            new Pagina( array(
              "titulo" => "rojo", "contenido" => "rrr ttt yyy"
            )),
            new Pagina( array(
              "titulo" => "verde", "contenido" => "uuu iii ooo",
              "subpages" => array(
                new Pagina( array(
                  "titulo" => "azul", "contenido" => "fff ggg hhh"
                ))
              )
            ))
          ) 
        )),
        new Pagina( array(
          "titulo" => "negro", "contenido" => "aaa sss ddd",
          "subpages" => array(
            new Pagina( array(
              "titulo" => "anaranjado", "contenido" => "jjj kkk lll"
            ))
          ) 
        )),
        new Pagina( array(
          "titulo" => "pardo", "contenido" => "qqq www eee"
        ))
      );
      
      $sps = $list[0]->getSubPages();
      $sps[0]->setOwner( $list[0] );
      $sps[1]->setOwner( $list[0] );
      
      $sps01 = $sps[1]->getSubpages();
      $sps01[0]->setOwner( $sps[1] );
      
      foreach ($list as $p)
        if (!$p->save()) print_r($p->getErrors());
      
      
      print_r( JSONPO::toJSONArray($list, true) );
      
      
      return $this->renderString('');
   }
}
?>