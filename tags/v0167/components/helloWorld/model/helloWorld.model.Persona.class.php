<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
class Persona extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->withTable = "hello_world_persona";

      $this->addAttribute("nombre", Datatypes :: TEXT);
      $this->addAttribute("edad",   Datatypes :: INT_NUMBER);

      $this->constraints = array (
         "nombre" => array (
            Constraint :: maxLength(30),
            Constraint :: blank(false)
         ),
         "edad" => array (
            Constraint :: between(10, 100)
         )
      );

      parent :: __construct($args, $isSimpleInstance);
   }

   public static function listAll($params)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: listAll($params);
   }
   public static function count()
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: count();
   }
   public static function get($id)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: get($id);
   }
   public static function findBy(Condition $condition, $params)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: findBy($condition, $params);
   }
   public static function countBy(Condition $condition)
   {
      self :: $thisClass = __CLASS__;
      return PersistentObject :: countBy($condition);
   }

}

?>