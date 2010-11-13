<?php

// PUEDEN HABER EVENTOS GLOBALES QUE PUEDEN OCURRIR EN CUALQUIERA DE LOS ESTADOS!!!!!!!!!
// Event es en verdad una transicion.
// Deberia tener el from state?
// DEBERIA TENER LAS ACCIONES QUE SE EJECUTAN AL HACER LA TRANSICION!
class Transition {
  
   //private $flow; // Flow al cual pertenece, para decirle que cambie de estado.
   private $eventName;
   private $sourceState;
   //private $targetState;
   private $targetStateName;
  
   /*
   public static function create( $eventName, $targetState )
   {
      return new Transition( $eventName, $targetState );
   }
   */
   /*
   private function __construct( $eventName, $targetState )
   {
      $this->eventName   = $eventName;
      $this->targetState = $targetState;
   }
   */

   public static function create( $eventName, $targetStateName )
   {
      return new Transition( $eventName, $targetStateName );
   }

   private function __construct( $eventName, $targetStateName )
   {
      $this->eventName       = $eventName;
      $this->targetStateName = $targetStateName;
   }
   
   public function getTargetStateName()
   {
      return $this->targetStateName;
   }

   public function setSourceState( $sourceState )
   {
      $this->sourceState = $sourceState;
   }

   public function getSourceState()
   {
      return $this->sourceState;
   }                            

   /*
   public function getTargetState()
   {
      return $this->targetState;
   }
   */

   public function getEventName()
   {
      return $this->eventName;
   }

   /*
   public function setFlow( $flow )
   {
      $this->flow = $flow;
   }

   public function getFlow()
   {
      return $this->flow;
   }
   */
}

?>