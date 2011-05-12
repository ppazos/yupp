<?php

/**
 * Clase modelo para el test M003.
 */

YuppLoader::load('tests.model.m003', 'Dedo');

class Dedo extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_m003_dedo");

      $this->belongsTo = array('Mano');
      
      $this->addHasOne('mano', 'Mano');

      $this->addAttribute("uniaLarga", Datatypes :: BOOLEAN);
      
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