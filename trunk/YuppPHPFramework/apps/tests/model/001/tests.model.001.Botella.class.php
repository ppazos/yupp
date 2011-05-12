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
} // Model001
?>