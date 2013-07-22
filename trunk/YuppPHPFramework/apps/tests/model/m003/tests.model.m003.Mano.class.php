<?php

/**
 * Clase modelo para el test M003.
 */

YuppLoader::load('tests.model.m003', 'Dedo');

class Mano extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_m003_mano");

      $this->addHasMany('dedos','Dedo'); // La unica diferencia con respecto al test i005

      $this->addAttribute("tamanio",  Datatypes :: TEXT);

      $this->addConstraints(
         "tamanio", array ( Constraint::inList( array("grande", "mediana", "chica") ) )
      );
      
      parent :: __construct($args, $isSimpleInstance);
   }
   
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}
?>