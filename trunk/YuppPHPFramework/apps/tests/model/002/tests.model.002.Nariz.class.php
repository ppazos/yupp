<?php

/**
 * Clase modelo para el test 002.
 */

class Nariz extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_002_nariz");
      
      $this->belongsTo = array('Cara');

      $this->addAttribute("tamanio",  Datatypes :: TEXT);

      $this->addConstraints(
         "tamanio",
         array (
            Constraint :: inList( array("chica", "mediana", "grande") )
         )
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