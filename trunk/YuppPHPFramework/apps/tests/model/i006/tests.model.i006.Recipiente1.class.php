<?php

/**
 * Clase modelo para el test I006.
 */

class Recipiente1 extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_i006_recipiente1");

      $this->addAttribute("material",  Datatypes :: TEXT);
      $this->addAttribute("capacidad", Datatypes :: FLOAT_NUMBER);
      $this->addAttribute("tieneTapa", Datatypes :: BOOLEAN);

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
         "tieneTapa",
         array (
            Constraint :: nullable(true)
         )
      );

      /*
      $this->constraints = array (
         "material" => array (
            Constraint :: maxLength(30),
            Constraint :: blank(false)
         ),
         "capacidad" => array (
            Constraint :: between(0.0, 10.0)
         ),
         "tieneTapa" => array (
            Constraint :: nullable(true)
         )
      );
      */

      parent :: __construct($args, $isSimpleInstance);
   }
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
} // Model006
?>