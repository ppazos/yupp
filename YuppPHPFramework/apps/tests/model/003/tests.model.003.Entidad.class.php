<?php

/**
 * Clase modelo para el test 003.
 */

class Entidad extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_003_entidad"); // BUG #19

      $this->addAttribute("tipo",  Datatypes :: TEXT);

      parent :: __construct($args, $isSimpleInstance);
   }
   
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>