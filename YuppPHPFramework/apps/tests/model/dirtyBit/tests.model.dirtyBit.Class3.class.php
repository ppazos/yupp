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
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>