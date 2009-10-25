<?php

// FIXME: tiene que ser singleton persistente, o por lo menos el locale.

class YuppContext {

   // Esto podria ser la referencia al router con el request ya procesado.
   private $component;     // componente actual
   private $controller;    // controller actual
   private $action;        // action actual
    
   private $params = array(); // parametros del request
   private $model  = array();  // Modelo devuelto por la ultima accion ejecutada
    
   private $locale = "es"; // actual locale seleccionado, en su forma de string, es_UY_xxxx
   private $mode   = YuppConfig::MODE_DEV; // Modo de ejecucion 

   //private static $instance = NULL;
   public static function getInstance()
   {
      // Deberia ser persistente asi concervo el locale entre requests... pero puede haber problemas con los params, aunque los params se resetean en cada request.
      //if (self::$instance === NULL) self::$instance = new YuppContext();
      //return self::$instance;
       
      $instance = NULL;
      if ( !YuppSession::contains("_yupp_context_singleton_instance") )
      {
         $instance = new YuppContext();
         YuppSession::set("_yupp_context_singleton_instance", $instance);
      }
      else
      {
         $instance = YuppSession::get("_yupp_context_singleton_instance");
      }

      return $instance;
   }
    
   private function __construct() {}
    
   public function setLocale( $locale )
   {
      // TODO: verificar que tiene formato correcto y es un locale valido.
      $this->locale = $locale;
   }
   public function getLocale()
   {
    	return $this->locale;
   }
    
   public function setMode( &$mode )
   {
      // TODO: ver que el modo es valido.
      $this->mode = $mode;
   }
   public function getMode()          { return $this->mode; }
    
   public function setComponent( $component ) { $this->component = $component; }
   public function getComponent()             { return $this->component; }
    
   public function setController( $controller ) { $this->controller = $controller; }
   public function getController()              { return $this->controller; }
    
   public function setAction( $action ) { $this->action = $action; }
   public function getAction()          { return $this->action; }
    
    
   public function setParams( &$params ) { $this->params = $params; }
   public function getParams()           { return $this->params; }
    
   public function setModel( &$model ) { $this->model = $model; }
   public function getModel()          { return $this->model; }
    
   public function update()
   {
      YuppSession::set("_yupp_context_singleton_instance", $this); // actualizo la variable en la session...
   }
}
?>