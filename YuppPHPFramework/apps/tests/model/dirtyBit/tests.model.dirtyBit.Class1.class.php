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
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>