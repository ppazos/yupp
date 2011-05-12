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