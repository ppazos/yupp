<?php

YuppLoader::load('tests.model.issue37', 'BBB');

class CCC extends BBB
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_issue37_ccc');
      
      $this->addAttribute('attrCCC',  Datatypes :: TEXT);
      
      $this->addConstraints('attrCCC' , array (
         Constraint :: minLength(5)
      ));
      
      parent :: __construct($args, $isSimpleInstance);
   }
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>