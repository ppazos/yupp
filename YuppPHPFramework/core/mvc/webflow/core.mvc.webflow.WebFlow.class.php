<?php

YuppLoader :: load('core.mvc.webflow', 'State');
YuppLoader :: load('core.mvc.webflow', 'Transition');

/**
 *
   $flow = WebFlow::create("ASDFG1234456564")
             ->add( // El primero que se agrega, por defecto, es el inicial!!!
               State::create( "start" )
                 ->add( Transition::create( "login", "shopping" ) )
                 ->add( Transition::create( "logout", "displayLogin" ) )
             )
             ->add(
               State::create( "shopping" )
                 ->add( Transition::create( "addToCart", "shopping" ) )
                 ->add( Transition::create( "funishShopping", "displayInvoice" ) )
             )
             ->add(
               State::create( "displayInvoice" ) // Un estado sin transiciones de salida es un estado final.
             )
             ->add(
               State::create( "displayLogin" )   // Un estado sin transiciones de salida es un estado final.
             );
*/

class WebFlow {
  
   private $flowId;   // Identificador de la instancia del flujo que se esta ejecutando, es enviada al cliente!
   private $states;   // Coleccion de estados disponibles.
   private $currentState;  // Estado actual
   private $previousState; // Estado anterior ??? necesario ??
   private $initialState;  // Estado inicial
   
   private $model;      // Objetos en scope del flow. Falta guardar el flow en sa session y listo.
   
//   private $actions;  // Acciones implementadas, son la logica que se ejecuta al entrar o salir de un estado. 

                        
   private $initialized = false;
   
   public function isInitialized()
   {
      return $this->initialized;
   }
                        
   // =======================================================================================
   // Las acciones se ejecutan:
   //  - Al entrar a un estado.
   //    * Se pueden hacer chequeos sobre el estado del sistema y ver si debe o no
   //      pasarse a ese estado, de no poder pasar a el, vuelve al anterior.
   //  - Al hacer la transicion entre estados.
   //    * Es el caso mas tipico, al hacer una transicion quiero modificar el
   //      estado de la aplicacion (mas aya de que se modifica el estado del flow),
   //      aqui las acciones trabajan como un controller.
   //
   // En cualquiera de los casos, lo bueno es que se pueden ejecutar varias acciones
   // de forma secuencial o verificar condiciones sobre el estado del flow o del sistema
   // y elegir entre ejecutar o no algunas acciones.
   //
   // ======================================================================================
   
   /**
    * getCurrentState
    * Devuelve el estado atual del flow
    * 
    * @return State
    */
   public function getCurrentState()
   {
      return $this->currentState;
   }
   
   /**
    * create
    * Crea un nuevo WebFlow con el identificador $flowId
    * 
    * @param String $flowId
    * @return WebFlow Nuevo web flow.
    * 
    */
   public static function create( $flowId )
   {
      return new WebFlow( $flowId );
   }
   
   private function __construct( $flowId )
   {
      $this->flowId  = $flowId;
      $this->states  = array();
      $this->actions = array();
      $this->model   = array();
      $this->previousState = NULL;
   }
   
   /**
    * getId
    * Devuelve el identificador del flow.
    * 
    * @return String Identificador del flow.
    */
   public function getId()
   {
      return $this->flowId;
   }
   
   /**
    * addToModel
    * Agrega un nuevo objeto al modelo del flow para que se almacene entre requests y mientras viva el flow.
    * 
    * @param String $key Clave por la que se obtiene el objeto que se guarda en el modelo.
    * @param Object $value Objeto que se guarda en el modelo del flow.
    */
   public function addToModel($key, $value)
   {
      $this->model[$key] = $value;
      
      // Se supone que el flow esta en CurrentFlows, hay que actualizarlo para que persista el estado entre requests
      CurrentFlows::getInstance()->update($this);
   }
   
   /**
    * getFromModel
    * Devuelve un objeto almacenado en el modelo por la clave $key.
    * 
    * @param String $key Clave por la que se pide el objeto almacenado en el modelo del flow.
    * @return Object Objecto almacenado en el modelo del flow.
    */
   public function getFromModel( $key )
   {
      return $this->model[$key];
   }
   
   /**
    * getModel
    * Devuelve todos los objetos almacenados en el modelo del flow.
    * 
    * @return Array Modelo del flow con todos sus objetos.
    */
   public function getModel()
   {
      return $this->model;
   }

   /**
    * Se debe llamar antes de empezar la ejecucion y luego de agregarle los estados.
    * Mueve los punteros de current, previous e initial a sus posiciones predeterminadas.
    */
   public function init()
   {
      // TODO: ver que hay algun estado.
      $this->initialized  = true;
      $this->resetf();
      
      // Se supone que el flow esta en CurrentFlows, hay que actualizarlo para que persista el estado entre requests
      CurrentFlows::getInstance()->update($this);
   }

   /**
    * add
    * Agrega un nuevo estado al flow.
    * 
    * @param State $state nuevo estado para agregar al flow.
    * @return WebFlow Devuelve el flow para poder hacer llamadas en cadena a add(state).
    */
   public function add( State $state )
   {
      $this->states[ $state->getName() ] = $state; // El nombre del estado debe ser unico!
      return $this; // retorna para poder llamar en cadena.
   }
   
   /**
    * get
    * Obtiene un estado por su nombre.
    * 
    * @param String $stateName Nombre del estado que se quiere pedir.
    * @return State Estado con nombre $stateName.
    */
   public function get( $stateName )
   {
      return $this->states[ $stateName ]; // Puede ser null
   }
   
   /**
    * resetf
    * Vuelve al flow a un estado conocido: el estado actual es el primer estado, se vacia el modelo.
    */
   public function resetf()
   {
      $this->initialState = current($this->states); // si le pongo states[0] no sirve xq no tiene esa key, como no uso iteradores, current me da siempre el primero del array.
      $this->currentState = current($this->states);
      $this->model = array();
   }
   

   
   /**
    * move
    * Se mueve al siguiente estado segun el evento y ejecuta acciones.
    * 
    * @param String $eventName Nombre del evento que se quiere ejecutar. 
    *        Si el estado actual no tiene una transicion de salida para dico evento, se arroja una excepcion.
    */
   function move( $eventName )
   {
      Logger::show( "WebFlow.move: currState: " . print_r($this->currentState->getName(), true) . ", " . __FILE__ . " " . __LINE__ );
      
      // Transition
      $newState = $this->currentState->get( $eventName );
      
      //Logger::show( "WebFlow.move: newState: " . print_r($newState->getTargetStateName(), true) . ", " . __FILE__ . " " . __LINE__ );
      
      if ($newState === NULL) throw new Exception("No existe la transicion del estado [". $this->currentState->getName() ."] por el evento [$eventName]");
      
      $newStateName = $newState->getTargetStateName(); // Puede ser null si no corresponde! p.e. falta registrar la transicion en el estado actual.
      if ($newStateName === NULL)
      {
         throw new Exception("La transicion [$eventName] no esta definida para el estado " . $this->currentState->getName());
      }
      
      // Si llega aqui, todo salio bien:
      // Cambio efectivo de estados
      $this->previousState = $this->currentState;
      $this->currentState = $this->states[$newStateName]; // TODO: verificar que existe?
    
      // Se supone que el flow esta en CurrentFlows, hay que actualizarlo para que persista el estado entre requests
      CurrentFlows::getInstance()->update($this);
      
      
      // TEST: ver si guarda el estado en la sesion
      //$test = CurrentFlows::getInstance()->getFlow( $this->flowId );
      //Logger::show( "Flow en sesion luego de hacer update: " . print_r($test->getCurrentState(), true) . ", " . __FILE__ . " " . __LINE__ );
      
      // TODO: que hago con data1 y data2 ????? Puede ser el modelo para la vista que hay que mostrar.
      // Aqui deberia mostrar alguna vista, igual que el controller al terminar su ejecucion.
   }

} // WebFlow

?>