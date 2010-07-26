<?php

class Movie extends PersistentObject
{
   protected $withTable = "movix_movie"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

   function __construct($args = array (), $isSimpleInstance = false)
   {
      // Definicion de campos
      //
      $this->addAttribute("name",   Datatypes :: TEXT);
      $this->addAttribute("alias",  Datatypes :: TEXT);
      $this->addAttribute("imdbid", Datatypes :: TEXT);
      $this->addAttribute("genres", Datatypes :: TEXT);
      $this->addAttribute("year",   Datatypes :: TEXT);
      $this->addAttribute("rating", Datatypes :: TEXT);
      $this->addAttribute("votes",  Datatypes :: TEXT);
      $this->addAttribute("url",    Datatypes :: TEXT);
      
      // Definicion de relaciones
      //
      
      // Inicializacion de campos
      //
      
      // Definicion de restriciones
      //
      $this->addConstraints("name", array (
         Constraint :: nullable(false),
         Constraint :: blank(false)
      ));

      parent :: __construct($args, $isSimpleInstance);
   }

   public static function listAll($params)
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

   public static function findBy(Condition $condition, $params)
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