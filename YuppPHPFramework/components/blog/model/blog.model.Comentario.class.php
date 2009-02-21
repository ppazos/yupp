<?php

YuppLoader::load( "blog.model", "Entrada" ); // Si no esta me tira error de que no encuentra Entrada cuando hago un YuppLoader.loadModel.

class Comentario extends Entrada {

    function __construct( $args = array(), $isSimpleInstance = false )
    {
       $this->addHasOne("entrada", 'EntradaBlog'); // Si fuera Entrada podria tener comentarios a los comnetarios.

       $this->addConstraints( "entrada", array( Constraint::nullable(false) ) );

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