<?php

YuppLoader::load('tests.model.issue37', 'AAA');

class BBB extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_issue37_bbb');

      $this->belongsTo = array('AAA');
      
      $this->addAttribute('attrBBB',  Datatypes :: TEXT);

      parent :: __construct($args, $isSimpleInstance);
   }
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>