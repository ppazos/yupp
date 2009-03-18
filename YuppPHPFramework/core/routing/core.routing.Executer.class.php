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
        $filters = new ControllerFilter2( $beforeFilters, $afterFilters ); // TODO: cambiar nombre a YuppControllerFilter.
        
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
//           echo "<pre>";
//           print_r( $ctx );
//           echo "</pre>";
         
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
   
// ================================================================================================
// Quiero arrojar esta except y que la agarre el try del index.php
//           try
//           {
              if ( $controllerInstance->flowExists($action) ) // Si es un web flow
              {
                 //Logger::show("ES FLOW");
               
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
           
                 // $flowExecutionResult puede ser "move", NULL o un codigo de error.
                 if ( $flowExecutionResult === NULL )
                 {
                 	  // TODO: debe mostrar la vista llamada: $currentState
                    $params = array_merge($flow->getModel(), $this->params);
                    $model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params ); // En los params no se que pasarle, deberian ser el modelo del flow mas los params submiteados en el request anterior.
                 }
                 else if ( $flowExecutionResult === "move" )
                 {
                 	  // TODO: pasar al siguiente estado, debe ejecutar la accion de pasar de estado o directamente ejecuta la siguiente accion?
                 
                    $eventName = $this->params["event"];
                    
                    //Logger::show( "EVENT: $eventName" );
                  
                    // ==============================================================================================
                    // FLOW MOVE!
                    $flow->move( $eventName ); // Cambia el estado si puede, si no, tira una except (no existe la transicion! => la maquina esta mal definida).

                    if ($flow->getCurrentState()->isEndState())
                    {
                       // Ejecutar accion del ultimo estado
                       $flowActionName = $flow->getCurrentState()->getName() . "Action";
                       
                       Logger::show("Estado final: " . $flow->getCurrentState()->getName());
                       
                       $controllerInstance->{$flowActionName}( &$flow ); // FIXME: podria retornar error y deberia volver al estado anterior y mostrar esa vista.
           
                       $params = array_merge($flow->getModel(), $this->params); // La accion puede haber agregado modelo (al flow o params del controller).
                       
                       // Quiero mostrar la vista correspondiente al nuevo estado. (antes de ejecutar init q cambia el estado!)
                       $model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params );
                    

                       // TODO: remover el flow del controller o inicializarlo de nuevo ya que se termino el que venia ejecutando.
                       $flow->init();
                    }
                    else
                    {
                       // Quiero mostrar la vista correspondiente al nuevo estado.
                       $model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params );
                    	  $params = array_merge($flow->getModel(), $this->params);
                    }
                 }
                 else
                 {
                 	  // TODO: tira un error, volver a la pagina actual y mostrar el error.
                    $controllerInstance->addToFlash("message", $flowExecutionResult); // Pongo el error en flash.message
                    
                    // Quiero mostrar la vista correspondiente al nuevo estado.
                    $params = array_merge($flow->getModel(), $this->params);
                    $model_or_command = $controllerInstance->render( $controller . "/" . $flow->getCurrentState()->getName(), &$params );
                 }
              }
              else // Es una accion comun.
              {
                 //Logger::show("ES ACCION COMUN");
                 $model_or_command = $controllerInstance->{$action}();
              }
//           }
//           catch (Exception $e)
//           {
//              echo $e->getMessage() . "<br/>";
//              echo $e->getTraceAsString();
//              exit();
//           }

           // ======================================================================================
           // PROCESA COMANDO (resultado de before_filters o de ejecucion del controlador)
           // ======================================================================================
           
           // Puede haber retornado solo modelo, un comando o nada.
           if ( is_array($model_or_command) ) // Es solo modelo
           {
              // =================
              // FIXME: falta agregarla al $model_or_command los params submiteados, o sea, $this->params.
              // RES: no parece ser necesario porque el modelo se crea sobre los params, ver como lo maneja 
              // el controller. O sea, me parece que al principio al controller se le dan los params submiteados
              // y luego el modelo se agrega a eso.
              // =================
            
              // Nombre de la vista se deriva del controller y la accion.
              $view = $controller . '/' . $action;
              
              $command = ViewCommand::display( $view, $model_or_command, $controllerInstance->getFlash() );
           }
           else if ( get_class( $model_or_command ) === 'ViewCommand' ) // Es comando (FIXME: no es lo mismo que instanceof?)
           {
              $command = $model_or_command;
           }
           else if ( $model_or_command === NULL ) // No retorno nada
           {
              // Nombre de la vista se deriva del controller y la accion.
              $view = $controller . '/' . $action;
              
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
              if ( !($af_res instanceof ViewCommand) ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($af_res));
              $command = $af_res; // Retorna el ViewCommand del after filter.
           }
           // ===================================================
        
        } // Paso los before filters y ejecuto accion del controller de forma normal
        
        // OK, tiene flash...
//        echo "<pre>";
//        print_r( $command );
//        echo "</pre>";

        return $command;
    }
}
?>