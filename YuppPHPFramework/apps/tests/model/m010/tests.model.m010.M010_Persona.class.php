<?php

/**
 * Clase modelo para el test 003.
 */
class M010_Persona extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_m010_persona");

      $this->addAttribute("nombre",  Datatypes :: TEXT);
      
      // Los hijos de la persona
      $this->addHasMany("hijos", "M010_Persona");
      
      $this->belongsTo = array('M010_Persona');

      parent :: __construct($args, $isSimpleInstance);
   }
   
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>