<?php

YuppLoader::load('tests.model.dobleHasManyB', 'BClass');

/**
 * Tiene 2 relaciones hasMany a la misma clase B.
 * El test intenta verificar las operaciones de PO
 * necesarias para gestionar estas relaciones de forma no ambigua.
 */
class AClass extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_hmb_aclass');

      $this->addAttribute('attrAClass',  Datatypes :: TEXT);

      $this->addHasMany('rolb1', 'BClass', self::HASMANY_COLLECTION, 'rel1');
      $this->addHasMany('rolb2', 'BClass', self::HASMANY_COLLECTION, 'rel2');

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