<?php

/**
 * Clase modelo para el test 001.
 */

class Botella extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_001_botella");

      $this->addAttribute("material",  Datatypes :: TEXT);
      $this->addAttribute("capacidad", Datatypes :: FLOAT_NUMBER);
      $this->addAttribute("tapaRosca", Datatypes :: BOOLEAN);

      $this->addConstraints(
         "material",
         array (
            Constraint :: maxLength(30),
            Constraint :: blank(false)
         )
      );
      $this->addConstraints(
         "capacidad",
         array (
            Constraint :: between(0.0, 10.0)
         )
      );
      $this->addConstraints(
         "tapaRosca",
         array (
            Constraint :: nullable(true)
         )
      );

      parent :: __construct($args, $isSimpleInstance);
   }
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
} // Model001
?>