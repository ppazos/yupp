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
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}
?>