<?php

class Class3 extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_dirty_bit_class3');

      $this->belongsTo = array('Class2');

      $this->addAttribute('attr31',  Datatypes :: TEXT);
      $this->addAttribute('attr32',  Datatypes :: DATE);
      $this->addAttribute('attr33',  Datatypes :: INT_NUMBER);

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