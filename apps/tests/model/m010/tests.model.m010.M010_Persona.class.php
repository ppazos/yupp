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
   
   public static function listAll( ArrayObject $params )
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
   public static function findBy(Condition $condition, ArrayObject $params)
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