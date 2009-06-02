<?php

class C1 extends C {

   function __construct( $args = array(), $isSimpleInstance = false )
   {
      $this->addAttribute("c1Text", Datatypes::TEXT);
      
      //$this->addHasMany("tieneMuchos", C1hasMany); // Coleccion OK
      //$this->addHasMany("tieneMuchos", C1hasMany, PersistentObject::HASMANY_SET); // OK
      $this->addHasMany("tieneMuchos", C1hasMany, PersistentObject::HASMANY_LIST);
      
      
      parent::__construct( $args, $isSimpleInstance );
   }


   public static function listAll( $params ) {
      self::$thisClass = __CLASS__;
      return PersistentObject::listAll( $params );
   }

   public static function count() {
      self::$thisClass = __CLASS__;
      return PersistentObject::count();
   }

   public static function get( $id ) {
      self::$thisClass = __CLASS__;
      return PersistentObject::get( $id );
   }

   public static function findBy( Condition $condition, $params ) {
      self::$thisClass = __CLASS__;
      return PersistentObject::findBy( $condition, $params );
   }

   public static function countBy( Condition $condition ) {
      self::$thisClass = __CLASS__;
      return PersistentObject::countBy( $condition );
   }
}

?>