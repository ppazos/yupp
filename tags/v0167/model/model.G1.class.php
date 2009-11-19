<?php

class G1 extends G {

    function __construct( $args = array(), $isSimpleInstance = false )
    {
        $this->addAttribute("g1Text", Datatypes::TEXT);

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