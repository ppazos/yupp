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