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
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}
?>