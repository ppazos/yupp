<?php

/**
 * Clase modelo para el test M003.
 */

YuppLoader::load('tests.model.m003', 'Mano');

class Dedo extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_m003_dedo");

      $this->belongsTo = array('Mano');
      
      $this->addHasOne('mano', 'Mano');

      $this->addAttribute("uniaLarga", Datatypes :: BOOLEAN);
      
      parent :: __construct($args, $isSimpleInstance);
   }
   
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}
?>