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
} // Model006
?>