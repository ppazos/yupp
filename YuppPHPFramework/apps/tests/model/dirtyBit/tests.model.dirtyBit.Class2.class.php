<?php

YuppLoader::load('tests.model.dirtyBit', 'Class3');

class Class2 extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_dirty_bit_class2');
      
      $this->belongsTo = array('Class1');

      $this->addAttribute('attr21',  Datatypes :: TEXT);
      $this->addAttribute('attr22',  Datatypes :: DATE);
      $this->addAttribute('attr23',  Datatypes :: INT_NUMBER);
      
      $this->addHasOne('class3', 'Class3');

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