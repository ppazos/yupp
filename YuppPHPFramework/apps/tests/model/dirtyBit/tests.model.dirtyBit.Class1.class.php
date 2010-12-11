<?php

YuppLoader::load('tests.model.dirtyBit', 'Class2');

class Class1 extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_dirty_bit_class1');

      $this->addAttribute('attr11',  Datatypes :: TEXT);
      $this->addAttribute('attr12',  Datatypes :: DATE);
      $this->addAttribute('attr13',  Datatypes :: INT_NUMBER);
      
      $this->addHasOne('class2', 'Class2');

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