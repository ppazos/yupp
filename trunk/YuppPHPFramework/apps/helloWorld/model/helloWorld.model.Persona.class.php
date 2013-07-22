<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
class Persona extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("hello_world_persona");

      $this->addAttribute("nombre", Datatypes :: TEXT);
      $this->addAttribute("edad",   Datatypes :: INT_NUMBER);

      $this->addConstraints("nombre", array (
          Constraint :: maxLength(30),
          Constraint :: blank(false)
      ));
      $this->addConstraints("edad", array (
          Constraint :: between(10, 100)
      ));

      parent :: __construct($args, $isSimpleInstance);
   }

   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>