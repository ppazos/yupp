<?php

/**
 * Clase modelo para el test a004.
 * 
 * Esta clase modela un arbol de paginas web.
 */

class Pagina extends PersistentObject
{
   function __construct($args = array (), $isSimpleInstance = false)
   {
      $this->setWithTable("test_a004_pagina");

      $this->addAttribute("titulo",  Datatypes :: TEXT);
      $this->addAttribute("contenido", Datatypes :: TEXT);
      
      // Pagina padre
      $this->addHasOne('owner', 'Pagina');
      
      // Paginas hijas
      $this->addHasMany('subpages', 'Pagina');


      $this->addConstraints("titulo", array (
         Constraint :: maxLength(255)
      ));
      $this->addConstraints("contenido", array (
         Constraint :: maxLength(100000)
      ));
      $this->addConstraints("owner", array (
         Constraint :: nullable(true) // Las paginas del primer nivel no tienen padre.
      ));

      parent :: __construct($args, $isSimpleInstance);
   }
   // Nueva para late static binding, usando solo esta se podrian borrar todas las otras operaciones
   public static function sgetClass()
   {
      return __CLASS__;
   }
} // Model001
?>