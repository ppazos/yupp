<?php

YuppLoader::load('tests.model.002', 'Cara');
YuppLoader::load('tests.model.002', 'Nariz');
YuppLoader::load('tests.model.a004', 'Pagina');
YuppLoader::load('core.persistent.serialize', 'JSONPO');

YuppLoader::load('tests.model.dobleHasManyB', 'AClass');

class ExtraTestsController extends YuppController {

   function dobleHasManyBGetAction()
   {
      Logger::getInstance()->on();
      $a = AClass::get(1);
      
      // Para que cargue pro lazy
      $bs1 = $a->getRolb1();
      $bs2 = $a->getRolb2(); // Estos tienen un hasMany a2 a AClass, quiero que lo cargue
      
      //echo '<pre>'. print_r($bs2, true) .'</pre>';
      
      foreach ($bs1 as $b)
      {
         $b->getA1(); // carga lazy del ho a $a
      }
      
      foreach ($bs2 as $b)
      {
         // FIXME: me dice que es un array vacio
         // Puede tener algo que ver conque las relaciones desde B a A por a2 se estan guardando como unidireccionales y son bidir...
         //$b->getA2(); // Carga lazy de hasMany 
         echo ('<pre> b - getA2 '. print_r($b->getA2(), true) .'</pre>');
      }
      
      echo "<h1>cascascscasdcad</h1>";
      
      return $this->renderString('<pre>'. print_r($a, true) .'</pre>');
   }

   function dobleHasManyBAction()
   {
      Logger::getInstance()->on();
      
      $a = new AClass(array('attrAClass'=>'un valor cualquiera'));
      $bs = array(
        new BClass(array('attrBClass'=>'asfasdfadfa')),
        new BClass(array('attrBClass'=>'sdfgsdfgshh')),
        //new BClass(array('attrBClass'=>'erterteeert')),
        //new BClass(array('attrBClass'=>'hjkhjhjkjkk')),
        //new BClass(array('attrBClass'=>'tyutytuyuyt')),
        //new BClass(array('attrBClass'=>'ioioioiooii'))
      );
      
      // Agrega algunos B a un hasMany y algunos al otro
      foreach ($bs as $i=>$b)
      {
         if ($i % 2 == 0)
         {
            $a->addToRolb1($b); // hasMany A->B
            $b->setA1($a); // hasOne B->A
         }
         else
         {
            $a->addToRolb2($b); // hasMany A->B
            //$b->setA1($a);
            $b->addToA2($a); // hasMany B->A
         }
      }
      
      if (!$a->save())
      {
         print_r($a->getErrors());
      }
      
      print_r($a);
      
      Logger::show('fin addTo');
      
      //$a->removeFromRolb1($bs[0]);
      
      //Logger::show('fin removeFrom');
      
      Logger::getInstance()->off();
      
      return $this->renderString('-=-=-=-=---=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=');
   }

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