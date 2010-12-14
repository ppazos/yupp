<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */

YuppLoader :: load('core.mvc.webflow', 'CurrentFlows');

class Executer {

    private $params;

    function __construct( $params )
    {
       $this->params = new ArrayObject( $params );
    }
    
    /**
     * @param componentControllerFiltersInstance instancia de ComponentControllerFilters, puede ser NULL.
     */
    public function execute( $componentControllerFiltersInstance )
    {
        // TODO: se le podria pasar el context como parametro porque en el llamador ya lo tengo, ahorro tener que pedirlo aca.
        $ctx = YuppContext::getInstance();
        $component  = $ctx->getComponent();
        $controller = $ctx->getController();
        $action     = $ctx->getAction();
        
        // Lo que se retorna de una accion de un controller o el command de un filtro cuando falla.
        $model_or_command = NULL;
        $command          = NULL; // Comando que voy a retornar.
        
        // ===================================================
        // Before y After filters para acciones de controllers
        $beforeFilters = ($componentControllerFiltersInstance !== NULL)? $componentControllerFiltersInstance->getBeforeFilters() : array();
        $afterFilters  = ($componentControllerFiltersInstance !== NULL)? $componentControllerFiltersInstance->getAfterFilters()  : array();
        $filters = new YuppControllerFilter( $beforeFilters, $afterFilters ); // TODO: cambiar nombre a YuppControllerFilter.
        
        // Ejecucion de los before filters, true si pasan o un ViewCommand si no.
        $bf_res = $filters->before_filter($component, $controller, $action, $this->params);
        
        // ===================================================
      
        if ( $bf_res !== true )
        {
           if ( !($bf_res instanceof ViewCommand) ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($bf_res));
           $command = $bf_res;
        }
        else // Si pasa el filtro, ejecuta la accion normalmente
        {
           $controllerClassName = strtoupper($controller[0]) . substr($controller, 1) . "Controller"; // El nombre de la clase es el que viene en la url + 'Controller''
   
           // echo "Controller Class Name 1: $controllerClassName<br/>";
   
           // Ya se verifico en RequestManager que el controller existe.
           YuppLoader::load( "apps.". $ctx->getComponent() .".controllers", $controllerClassName );

           // Debe verificar si tiene la accion y si la puede ejecutar, si no va a index.
           // FIXME: para que pasarle el nombre del controller al mismo controller???
           $controllerInstance = new $controllerClassName( $this->params ); // Se usa abajo!!!
   
           // Si hay except la agarra en el try del index.php
           if ( $controllerInstance->flowExists($action) ) // Si es un web flow
           {
              Logger::show("ES FLOW " . __FILE__ . " " . __LINE__);
              
              // TODO: hacer un metodo flowHandler para achicar el codigo
              
              if (!$controllerInstance->flowLoaded($action))
              {
                 Logger::show("== FLOW NOT LOADED == ".__FILE__.' '.__LINE__);
                 $controllerInstance->loadFlow($action);
              }
            
              // ===============================================================================
              // Get Flow from controller
              $flow = $controllerInstance->getFlow($action); // La accion es el nombre del flow.
              
              if (!$flow->isInitialized())
              {
                 Logger::show("== FLOW NO INICIALIZADO == ".__FILE__.' '.__LINE__);
                 $flow->init();
              }
              
              Logger::show("== FLOW currentState: ".print_r($flow->getCurrentState(),true).' '.__FILE__.' '.__LINE__);
              
              // ===============================================================================
              // Execute Controller Flow Action
              $flowActionName = $flow->getCurrentState()->getName() . "Action";
              
              Logger::show("== ACCION: $flowActionName == ".__FILE__.' '.__LINE__);
              
              // Esta es ya la accion de pasar al otro estado.??? deberia ser en el move...
              $flowExecutionResult = $controllerInstance->{$flowActionName}( $flow );
        
              Logger::show( "Salida del flow: $flowExecutionResult, " . __FILE__ . " " . __LINE__ );
        
              // $flowExecutionResult puede ser "move", NULL o un codigo de error.
              if ( $flowExecutionResult === NULL )
              {
                 Logger::show( "Flow Execution Result = NULL, " . __FILE__ . " " . __LINE__ );
               
                 // TODO: debe mostrar la vista llamada: $currentState
                 
                 $controllerInstance->addToParams( $flow->getModel() ); // Hace lo mismo que la linea de arriba. Los params son los del request y los del flow.
                 
                 $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName() );
              }
              else if ( $flowExecutionResult === "move" )
              {
                 // TODO: pasar al siguiente estado, debe ejecutar la accion de pasar de estado o directamente ejecuta la siguiente accion?
                 Logger::show( "Flow Execution Result = MOVE, " . __FILE__ . " " . __LINE__ );
              
                 $eventName = $this->params["event"];
                 
                 //Logger::show( "EVENT: $eventName, " . __FILE__ . " " . __LINE__ );
               
                 // ==============================================================================================
                 // FLOW MOVE!
                 $flow->move( $eventName ); // Cambia el estado si puede, si no, tira una except (no existe la transicion! => la maquina esta mal definida).


                 if ($flow->getCurrentState()->isEndState())
                 {
                    // Ejecutar accion del ultimo estado
                    $flowActionName = $flow->getCurrentState()->getName() . "Action";
                    
                    Logger::show("Executer (END STATE): " . $flow->getCurrentState()->getName() . ", " . __FILE__ . " " . __LINE__ );
                    
                    /// FIXME: podria retornar error y deberia volver al estado anterior y mostrar esa vista.
                    $controllerInstance->{$flowActionName}( $flow );
                    
                    // La accion puede haber agregado modelo (al flow o params del controller).
                    $controllerInstance->addToParams( $flow->getModel() ); // Hace lo mismo que la linea de arriba. Los params son los del request y los del flow.
                    
                    // Quiero mostrar la vista correspondiente al nuevo estado. (antes de ejecutar init q cambia el estado!)
                    $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName() );

                    // TODO: remover el flow del controller o inicializarlo de nuevo ya que se termino el que venia ejecutando.
                    $flow->init();
                 }
                 else
                 {
                    Logger::show( "Executer (NO ES END STATE) currentState: " . $flow->getCurrentState()->getName() . ", " . __FILE__ . " " . __LINE__ );
                    
                    // Quiero mostrar la vista correspondiente al nuevo estado.
                    $controllerInstance->addToParams( $flow->getModel() ); // Los params son los del request y los del flow.
                    $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName() );
                 }
              }
              else
              {
                 Logger::show( "Flow Execution Result NO es MOVE, " . __FILE__ . " " . __LINE__ );
               
                 // TODO: tira un error, volver a la pagina actual y mostrar el error.
                 $controllerInstance->addToFlash("message", $flowExecutionResult); // Pongo el error en flash.message
                 
                 // Quiero mostrar la vista correspondiente al nuevo estado.
                 $controllerInstance->addToParams( $flow->getModel() );
                 
                 $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName() );
              }
           }
           else // Es una accion comun.
           {
              // Si hay algun flow activo y ejecuto una accion comun, tengo que resetearlos 
              // (porque sali del flow y si vuelvo a ejecutar el flow puede estar en un estado inconsistente).
              CurrentFlows::getInstance()->resetFlows(); // Se encarga de verificar si hay algun flow para resetear

              //Logger::show("ES ACCION COMUN, " . __FILE__ . " " . __LINE__ );
              
              // FIXME: la instancia del controller se crea con la accion como parametro,
              //        si ya se sabe que accion se va a ejecutar,
              //        para que hacer esta llamada con la accion como variable ???.
              try
              {
                 $model_or_command = $controllerInstance->{$action}();
              }
              catch (Exception $e)
              {
                 // No existe la accion o cualquier otra excepcion que pueda tirar
                 // Tira 500: Internal Server Error
                 $model_or_command = ViewCommand::display( '500',
                                          new ArrayObject(array('message'=>$e->getMessage().'<br/><pre>'.$e->getTraceAsString().'</pre>')),
                                          new ArrayObject() );
              }
              
           }

           // ======================================================================================
           // PROCESA COMANDO (resultado de before_filters o de ejecucion del controlador)
           // ======================================================================================
           
           //Logger::struct( $model_or_command, "MODEL OR COMMAND, " . __FILE__ . " " . __LINE__ );
           
           // Puede haber retornado un comando, params como array, o nada (se toman los params del controller)
           
           // Error en 0.1.6.7
           // Si no verifico por null antes que por get_class, get_class(NULL me tira error en la ultima version de PHP).
           if ( $model_or_command === NULL ) // No retorno nada
           {
              // Nombre de la vista es la accion.
              $view = $action;
              
              // El modelo que se devuelve es solo los params submiteados.
              $command = ViewCommand::display( $view, $controllerInstance->getParams(), $controllerInstance->getFlash() );
           }
           else if ( is_array($model_or_command) ) // Si la accion del controller retorna los params en lugar de ponerlos en $this->params
           {
              // Nombre de la vista es la accion.
              $view = $action;
              
              $returnedParams = new ArrayObject( $model_or_command );

              // Se juntan los params con el arrray devuelto
              // Tengo que transformar getParams a array porque es ArrayObject
              $allparams = array_merge( (array)$controllerInstance->getParams(), $model_or_command );
              
              // El modelo que se devuelve es solo los params submiteados.
              // Tengo que transformar allParams a ArrayObject porque es lo que espera el metodo display()
              $command = ViewCommand::display( $view, new ArrayObject($allparams), $controllerInstance->getFlash() );
           }
           else if ( get_class( $model_or_command ) === 'ViewCommand' ) // Es comando (FIXME: no es lo mismo que instanceof?)
           {
              $command = $model_or_command;
           }
           else
           {
              // FIXME: error 500
              // CASO IMPOSIBLE, ACCION DE CONTROLLER RETORNA OTRA COSA.
              //print_r( $model_or_command );
              //throw new Exception('Error: verifique lo que retorna de la accion: '. $controller.'::'.$action);
              $command = ViewCommand::display( '500',
                                               new ArrayObject( array('message'=>'Error: verifique lo que retorna de la accion: '. $controller.'::'.$action)),
                                               new ArrayObject() );
           }
           
           // ===================================================
           // after filters
           // Ejecucion de los after filters, true si pasan o un ViewCommand si no.
           $af_res = $filters->after_filter($component, $controller, $action, $this->params, $command);
           
           if ( $af_res !== true )
           {
              if ( get_class($af_res) !== 'ViewCommand' ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($af_res));
              $command = $af_res; // Retorna el ViewCommand del after filter.
           }
           // ===================================================
        
        } // Paso los before filters y ejecuto accion del controller de forma normal
        
        return $command;
    }
}
?>