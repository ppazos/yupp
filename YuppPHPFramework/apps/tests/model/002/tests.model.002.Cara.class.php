<?php

/**
 * Clase modelo para el test 002.
 */

YuppLoader::load('tests.model.002', 'Nariz');

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
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
}

?>