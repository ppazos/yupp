<?php

/**
 * Clase modelo para el test 002.
 */

class Cara extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable('test_002_cara');

      $this->addAttribute('color', Datatypes :: TEXT);
      $this->addHasOne('nariz', 'Nariz');

      $this->addConstraints(
         'color',
         array (
            //Constraint :: inList( array(NULL, 'blanco', 'negro', 'pardo') ),
            // No es necesario que la lista tenga el valor NULL para ser compatible
            // con la restriccon nullable(true), si el valor es NULL y pasa la
            // restriccion nullable, no se verifica el inList y lo da como valido.
            Constraint :: inList( array('blanco', 'negro', 'pardo') ),
            Constraint :: nullable( true )
         )
      );

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