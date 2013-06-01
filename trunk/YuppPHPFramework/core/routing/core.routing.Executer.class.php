<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
class Executer {

   private $params;

   function __construct( $params )
   {
      $this->params = new ArrayObject( $params );
   }

   /**
    * @param appControllerFiltersInstance instancia de AppControllerFilters, puede ser NULL.
    */
   public function execute( $appControllerFiltersInstance )
   {
      // TODO: se le podria pasar el context como parametro porque en el llamador ya lo tengo, ahorro tener que pedirlo aca.
      $ctx = YuppContext::getInstance();
      $app        = $ctx->getApp();
      $controller = $ctx->getController();
      $action     = $ctx->getAction();

      // Lo que se retorna de una accion de un controller o el command de un filtro cuando falla.
      $model_or_command = NULL;
      $command          = NULL; // Comando que voy a retornar.

      // ===================================================
      // Before y After filters para acciones de controllers
      $beforeFilters = ($appControllerFiltersInstance !== NULL)? $appControllerFiltersInstance->getBeforeFilters() : array();
      $afterFilters  = ($appControllerFiltersInstance !== NULL)? $appControllerFiltersInstance->getAfterFilters()  : array();
      $filters = new YuppControllerFilter( $beforeFilters, $afterFilters ); // TODO: cambiar nombre a YuppControllerFilter.

      // Ejecucion de los before filters, true si pasan o un ViewCommand si no.
      $bf_res = $filters->before_filter($app, $controller, $action, $this->params);
      // ===================================================

      if ( $bf_res !== true )
      {
         Logger::getInstance()->log("Resultado del filter NO ES TRUE!!!");
        
         if ( !($bf_res instanceof ViewCommand) ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($bf_res));
         $command = $bf_res;
      }
      else // Si pasa el filtro, ejecuta la accion normalmente
      {
         $controllerClassName = strtoupper($controller[0]) . substr($controller, 1) . "Controller"; // El nombre de la clase es el que viene en la url + 'Controller''
         // echo "Controller Class Name 1: $controllerClassName<br/>";

         // Ya se verifico en RequestManager que el controller existe.
         YuppLoader::load( "apps.". $ctx->getApp() .".controllers", $controllerClassName );

         // Debe verificar si tiene la accion y si la puede ejecutar, si no va a index.
         // FIXME: para que pasarle el nombre del controller al mismo controller???

         $controllerInstance = new $controllerClassName( $this->params ); // Se usa abajo!!!
         
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
                                  new ArrayObject(array('message'=>$e->getMessage(), 'traceString'=>$e->getTraceAsString(), 'trace'=>$e->getTrace(), 'exception'=>$e)),
                                  new ArrayObject() );
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
         $af_res = $filters->after_filter($app, $controller, $action, $this->params, $command);
           
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