<?php

/**
 * Almacena los web flows que estan en ejecucion en este momento. Espersistente entre requests.
 * Pregunta: es posible que un usuario tenga mas de un flow ejecutandose en el mismo momento?
 * ESTO PASARIA SOLO SI EL FLOW TIENE SUBFLOWS, PERO SI SALE DEL FLOW, ESTE TENDRIA QUE ELIMINARSE, O NO?.
 * Tal vez el controller deberia guardar el flow en ejecucion y manejarlo el (y administrar la key).
 */
class CurrentFlows {

   private $flows = array();

   public static function &getInstance()
   {
      $instance = NULL;
      if ( !YuppSession::contains("_current_flows_singleton_instance") )
      {
         $instance = new CurrentFlows();
         YuppSession::set("_current_flows_singleton_instance", $instance);
      }
      else
      {
         $instance = YuppSession::get("_current_flows_singleton_instance");
      }

      return $instance;
   }
   
   public function update( WebFlow $flow )
   {
      //$this->addFlow( &$flow ); // Para hacer update es necesario agregarlo de nuevo :S de otra forma no funciona.
      $this->addFlow( $flow );
   }

   private function __construct()
   {
   }

   //public function addFlow( WebFlow &$flow )
   public function addFlow( WebFlow $flow )
   {
      $this->flows[ $flow->getId() ] = $flow;
      YuppSession::set("_current_flows_singleton_instance", $this); // actualizo la variable en la session...
   }
   
   public function &getFlow( $flowId )
   {
   	return $this->flows[ $flowId ];
   }
   
   /**
    * Devuelve true si tiene algun flow, false si no.
    */
   public function hasAnyFlow()
   {
      return ( count($this->flows) !== 0 );
   }
   
   /**
    * Devuelve la cantidad de flows.
    */
   public function getFlowCount()
   {
      return count($this->flows);
   }
   
   /**
    * Si hay algun flow, los vuelve a su estado original mediante 'init()'.
    */
   public function resetFlows()
   {
      if ( $this->hasAnyFlow() )
      {
         foreach ( $this->flows as $flow ) $flow->init(); // OJO capaz que no cambia los objetos dentro del array! (funciona OK)
         YuppSession::set("_current_flows_singleton_instance", $this);
      } 
   }

   // Para testing
   public static function dump()
   {
      echo '<pre>';
      print_r( self::getInstance() );
      echo '</pre>';
   }
}

?>