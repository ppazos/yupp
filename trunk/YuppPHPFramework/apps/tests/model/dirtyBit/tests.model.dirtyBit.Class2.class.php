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
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>