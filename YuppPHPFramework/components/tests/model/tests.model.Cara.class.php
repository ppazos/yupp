<?php

/**
 * Clase modelo para el test 002.
 */

class Cara extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->withTable = "test_002_cara";

      $this->addAttribute("color",  Datatypes :: TEXT);
      $this->addHasOne("nariz", 'Nariz');

      $this->constraints = array (
         "color" => array (
            Constraint :: inList( array("blanco", "negro", "pardo") )
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