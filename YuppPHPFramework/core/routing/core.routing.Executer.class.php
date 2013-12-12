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
      $filters = new YuppControllerFilter( $beforeFilters, $afterFilters );

      // FIXME: no llamar sino hay beforeFilters
      // Ejecucion de los before filters, true si pasan o un ViewCommand si no.
      $bf_res = $filters->before_filter($app, $controller, $action, $this->params);
      // ===================================================

      // Sino pasa el beforeFilter
      // ===================================================
      if ( $bf_res !== true )
      {
         Logger::getInstance()->log("Resultado del filter NO ES TRUE!!!");
        
         if ( !($bf_res instanceof ViewCommand) ) throw new Exception("After filter no retorna ViewCommand, retorna " . get_class($bf_res));
         $command = $bf_res;
         return $command;
      }
      // ===================================================
      
      
      // Ejecuta accion del controllador
      YuppLoader::load('core.app', 'App');
      $app = App::getCurrent();
      $command = $app->execAction($controller, $action, $this->params);

      
      // FIXME: no llamar sino hay afterFilters
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

      return $command;
   }
}
?>