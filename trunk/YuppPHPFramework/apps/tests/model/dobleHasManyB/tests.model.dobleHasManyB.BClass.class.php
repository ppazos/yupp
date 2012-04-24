<?php

YuppLoader::load('tests.model.dobleHasManyB', 'AClass');

class BClass extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_hmb_bclass');

      $this->addAttribute('attrBClass',  Datatypes :: TEXT);
      
      $this->addHasOne('a1', 'AClass', 'rel1'); // rel bidir con AClass.rolb1
      $this->addHasMany('a2', 'AClass', self::HASMANY_COLLECTION, 'rel2'); // rel bidir con AClass.rolb2

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