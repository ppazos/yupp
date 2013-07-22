<?php

/**
 * Clase modelo para el test 003.
 */

YuppLoader::load("tests.model.003", "Entidad");

class TestPersona extends Entidad
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_003_persona"); // BUG #19

      $this->addAttribute("nombre",  Datatypes :: TEXT);
      $this->addAttribute("edad",  Datatypes :: INT_NUMBER);
      $this->addAttribute("num",  Datatypes :: INT_NUMBER);

      $this->addConstraints(
         "edad", array ( Constraint :: min(10) )
      );
      $this->addConstraints(
         "num", array ( Constraint :: max(20) )
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