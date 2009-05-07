<?php

/**
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
class Executer {

    private $params = array();

    function Executer( & $params )
    {
       $this->params = $params;
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
        $bf_res = $filters->before_filter($component, $controller, $action, &$this->params);
        
        // ===================================================
      
        if ( $bf_res !== true )
        {
        	  if ( !($bf_res instanceof ViewCommand) ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($bf_res));
           $command = $bf_res; //
        }
        else // Si pasa el filtro, ejecuta la accion normalmente
        {
       	  $controllerClassName = strtoupper($controller[0]) . substr($controller, 1) . "Controller"; // El nombre de la clase es el que viene en la url + 'Controller''
   
           try
           {
              // Debe chekear existencia y si no existe, va a core controller
              YuppLoader::load( "components.". $ctx->getComponent() .".controllers", $controllerClassName  );
           }
           catch (Exception $e)
           {
              $controllerClassName = "CoreController";
           	  YuppLoader::load( "components.core.controllers", $controllerClassName  );
           }
   
           // Debe verificar si tiene la accion y si la puede ejecutar, si no va a index.
           $controllerInstance = new $controllerClassName($controller, $action, $this->params); // Se usa abajo!!!
   


           // Si hay except la agarra en el try del index.php

           if ( $controllerInstance->flowExists($action) ) // Si es un web flow
           {
              //Logger::show("ES FLOW " . __FILE__ . " " . __LINE__);
            
              if (!$controllerInstance->flowLoaded($action))
              {
                //Logger::show("== FLOW NOT LOADED ==");
              	 $controllerInstance->loadFlow($action);
              }
            
              // ===============================================================================
              // Get Flow from controller
           	  $flow = $controllerInstance->getFlow($action); // La accion es el nombre del flow.
              
              if (!$flow->isInitialized())
              {
              	  $flow->init();
              }
              
              // ===============================================================================
              // Execute Controller Flow Action
              $flowActionName = $flow->getCurrentState()->getName() . "Action";
              $flowExecutionResult = $controllerInstance->{$flowActionName}( &$flow ); // Esta es ya la accion de pasar al otro estado.??? deberia ser en el move...
        
              Logger::show( "Salida del flow: $flowExecutionResult, " . __FILE__ . " " . __LINE__ );
        
              // $flowExecutionResult puede ser "move", NULL o un codigo de error.
              if ( $flowExecutionResult === NULL )
              {
                 Logger::show( "Flow Execution Result = NULL, " . __FILE__ . " " . __LINE__ );
               
              	  // TODO: debe mostrar la vista llamada: $currentState
                 $params = array_merge($flow->getModel(), $this->params);
                 //$model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params ); // En los params no se que pasarle, deberian ser el modelo del flow mas los params submiteados en el request anterior.
                 $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName(), &$params ); // En los params no se que pasarle, deberian ser el modelo del flow mas los params submiteados en el request anterior.
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
                    
                    //Logger::show("END STATE: " . $flow->getCurrentState()->getName() . ", " . __FILE__ . " " . __LINE__ );
                    
                    $controllerInstance->{$flowActionName}( &$flow ); // FIXME: podria retornar error y deberia volver al estado anterior y mostrar esa vista.
        
                    $params = array_merge($flow->getModel(), $this->params); // La accion puede haber agregado modelo (al flow o params del controller).
                    
                    // Quiero mostrar la vista correspondiente al nuevo estado. (antes de ejecutar init q cambia el estado!)
                    //$model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params );
                    $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName(), &$params );

                    // TODO: remover el flow del controller o inicializarlo de nuevo ya que se termino el que venia ejecutando.
                    $flow->init();
                 }
                 else
                 {
                    //Logger::show( "NO ES END STATE: " . $flow->getCurrentState()->getName() . ", " . __FILE__ . " " . __LINE__ );
                    
                    // Quiero mostrar la vista correspondiente al nuevo estado.
                    //$model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params );
                    $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName(), &$params );
                 	  $params = array_merge($flow->getModel(), $this->params);
                 }
              }
              else
              {
                 Logger::show( "Flow Execution Result NO es MOVE, " . __FILE__ . " " . __LINE__ );
               
              	  // TODO: tira un error, volver a la pagina actual y mostrar el error.
                 $controllerInstance->addToFlash("message", $flowExecutionResult); // Pongo el error en flash.message
                 
                 // Quiero mostrar la vista correspondiente al nuevo estado.
                 $params = array_merge($flow->getModel(), $this->params);
                 //$model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params );
                 $model_or_command = $controllerInstance->render( $flow->getCurrentState()->getName(), &$params );
              }
           }
           else // Es una accion comun.
           {
              // Si hay algun flow activo y ejecuto una accion comun, tengo que resetearlos 
              // (porque sali del flow y si vuelvo a ejecutar el flow puede estar en un estado inconsistente).
              CurrentFlows::getInstance()->resetFlows(); // Se encarga de verificar si hay algun flow para resetear
              

              //Logger::show("ES ACCION COMUN, " . __FILE__ . " " . __LINE__ );
              $model_or_command = $controllerInstance->{$action}();
           }

           // ======================================================================================
           // PROCESA COMANDO (resultado de before_filters o de ejecucion del controlador)
           // ======================================================================================
           
           //Logger::struct( $model_or_command, "MODEL OR COMMAND, " . __FILE__ . " " . __LINE__ );
           
           // Puede haber retornado solo modelo, un comando o nada.
           if ( is_array($model_or_command) ) // Es solo modelo
           {
              // =================
              // FIXME: falta agregarla al $model_or_command los params submiteados, o sea, $this->params.
              // RES: no parece ser necesario porque el modelo se crea sobre los params, ver como lo maneja 
              // el controller. O sea, me parece que al principio al controller se le dan los params submiteados
              // y luego el modelo se agrega a eso.
              // =================
            
              // Nombre de la vista es la accion.
              $view = $action; // $controller . '/' . $action;
              
              // $model_or_command incluye los params submiteados!
              $command = ViewCommand::display( $view, $model_or_command, $controllerInstance->getFlash() );
           }
           else if ( get_class( $model_or_command ) === 'ViewCommand' ) // Es comando (FIXME: no es lo mismo que instanceof?)
           {
              $command = $model_or_command;
           }
           else if ( $model_or_command === NULL ) // No retorno nada
           {
              // Nombre de la vista es la accion.
              $view = $action; // $controller . '/' . $action;
              
              // El modelo que se devuelve es solo los params submiteados.
              $command = ViewCommand::display( $view, $this->params, $controllerInstance->getFlash() );
           }
           else
           {
              // CASO IMPOSIBLE, ACCION DE CONTROLLER RETORNA OTRA COSA.
           }
           
//        echo "<pre>";
//        print_r( $model_or_command );
//        echo "</pre>";
           
           // ===================================================
           // after filters
           // Ejecucion de los after filters, true si pasan o un ViewCommand si no.
           $af_res = $filters->after_filter($component, $controller, $action, &$this->params, $command);
           
           if ( $af_res !== true )
           {
              if ( get_class($af_res) !== 'ViewCommand' ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($af_res));
              //if ( !($af_res instanceof ViewCommand) ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($af_res));
              $command = $af_res; // Retorna el ViewCommand del after filter.
           }
           // ===================================================
        
        } // Paso los before filters y ejecuto accion del controller de forma normal
        
        return $command;
    }
}
?>