<?php

/**
 * Clase modelo para el test I006.
 */

class Contenido1 extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_i006_contenido1");

      $this->belongsTo = array('Vaso1'); // La unica diferencia con respecto al test i005

      $this->addAttribute("elemento",  Datatypes :: TEXT);
      $this->addAttribute("volumen", Datatypes :: FLOAT_NUMBER);

      $this->addConstraints(
         "elemento",
         array (
            Constraint :: maxLength(30),
            Constraint :: blank(false)
         )
      );
      $this->addConstraints(
         "volumen",
         array (
            Constraint :: between(0.0, 10.0)
         )
      );

      /*
      $this->constraints = array (
         "elemento" => array (
            Constraint :: maxLength(30),
            Constraint :: blank(false)
         ),
         "volumen" => array (
            Constraint :: between(0.0, 10.0)
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