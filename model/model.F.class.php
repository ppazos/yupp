<?php

class F extends B {

    protected $withTable = "fs"; // si lo seteo en el contructor se setea para los hijos aunque se defina un wt para ellos (xq se llama en el constructor...)

    function __construct( $args = array(), $isSimpleInstance = false )
    {
        //$this->withTable = "entradas";

        $this->addAttribute("ffft", Datatypes::TEXT);
        $this->addAttribute("ffff", Datatypes::DATETIME);

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