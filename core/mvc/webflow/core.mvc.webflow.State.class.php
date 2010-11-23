<?php

class State {

   private $name;
   private $transitions; // Transiciones que se pueden dar para salir del estado actual.
   
   public static function create( $name )
   {
      return new State($name);
   }

   public function __construct( $name )
   {
      $this->name = $name;
      $this->transitions = array();
   }

   public function getName()
   {
      return $this->name;
   }

   public function add( Transition $transition )
   {
      $transition->setSourceState( $this );
      $this->transitions[ $transition->getEventName() ] = $transition;
      return $this; // Retorna el objeto para concatenar llamadas.
   }
   
   public function get( $eventName )
   {
      // TODO: chekeo de que existe?
   	return $this->transitions[ $eventName ];
   }
   
   public function isEndState()
   {
   	return ( count( $this->transitions ) === 0 );
   }

} // State

?>