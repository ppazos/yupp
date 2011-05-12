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


      $this->addConstraints(
         "titulo",
         array (
            Constraint :: maxLength(255)
         )
      );
      $this->addConstraints(
         "contenido",
         array (
            Constraint :: maxLength(100000)
         )
      );
      $this->addConstraints(
         "owner",
         array (
            Constraint :: nullable(true) // Las paginas del primer nivel no tienen padre.
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
} // Model001
?>