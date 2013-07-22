<?php

YuppLoader::load('tests.model.issue37', 'BBB');

class AAA extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_issue37_aaa');
      
      $this->addHasMany('bs', 'BBB');

      parent :: __construct($args, $isSimpleInstance);
   }
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>