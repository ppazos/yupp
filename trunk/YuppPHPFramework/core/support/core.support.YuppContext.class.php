<?php

// FIXME: tiene que ser singleton persistente, o por lo menos el locale.
// FIXME: tiene informacion duplicada con el Router.

class YuppContext {

   // Esto podria ser la referencia al router con el request ya procesado.
   private $app;            // aplicacion actual
   private $controller;     // controller actual
   private $action;         // action actual
   
   private $realApp = NULL; // Nombre de la aplicacion real. Se usa cuando se esta en core (app=core) 
                            // pero se quiere realizar alguna operacion en la base para una clase de una 
                            // aplicacion particular, y se necesite el nombre de la app para crear la DAL 
                            // con la config de esa app. Esto ultimo lo hace PM.
   
   private $params = array(); // parametros del request
   private $model  = array(); // Modelo devuelto por la ultima accion ejecutada
    
   private $locale = "es"; // actual locale seleccionado, en su forma de string, es_UY_xxxx
   
   // FIXME: el modo deberia ser configurable, no deberia estar aca fijo.
   // FIXME: el modo se define en YuppConfig, no deberia duplicarse. De ultima si se quiere acceder al modo de ejecucion desde el contexto, que haya una operacion aca, que lo lea desde YuppConfig.
   private $mode   = YuppConfig::MODE_DEV; // Modo de ejecucion 
   
   private static $instance = NULL;

   public static function getInstance()
   {
      // Deberia ser persistente asi concervo el locale entre requests...
      // pero puede haber problemas con los params, aunque los params se resetean en cada request.
      // FIXED: el locale es lo unico que se persiste entre requests.
      if (self::$instance === NULL) self::$instance = new YuppContext();
      return self::$instance;
   }
    
   private function __construct()
   {
      // yupp_locale debe ser persistente para recordar la eleccion del usuario.
      if ( YuppSession::contains("_yupp_locale") )
      {
         $this->locale = YuppSession::get("_yupp_locale");
      }
   }
    
   public function setLocale( $locale )
   {
      // TODO: verificar que tiene formato correcto y es un locale valido.
      $this->locale = $locale;
      YuppSession::set("_yupp_locale", $locale);
   }
   public function getLocale()
   {
      return $this->locale;
   }
    
   public function setMode( $mode )
   {
      // TODO: ver que el modo es valido.
      $this->mode = $mode;
   }
   public function getMode() { return $this->mode; }
    
   public function setApp( $app ) { $this->app = $app; }
   public function getApp() { return $this->app; }
    
   public function setController( $controller ) { $this->controller = $controller; }
   public function getController() { return $this->controller; }
    
   public function setAction( $action ) { $this->action = $action; }
   public function getAction() { return $this->action; }
   
   public function setParams( $params ) { $this->params = $params; }
   public function getParams() { return $this->params; }
    
   public function setModel( $model ) { $this->model = $model; }
   public function getModel() { return $this->model; }
   
   public function setRealApp( $appName ) { $this->realApp = $appName; }
   public function getRealApp() { return $this->realApp; }
   public function isAnotherApp() { return ($this->realApp != NULL && $this->realApp != $this->app); }
}
?>