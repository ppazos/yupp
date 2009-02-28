<?php

/*
 * Singleton persistente.
 *
 * Modela el historial de paginas visitadas. Cuando una pagina es mostrada, se le informa al PageHistory y este la guarda.
 * Tambien puede obtenerse le pagina mostrada antes (puede servir para saber a que pagina ir en caso de error), o todo el
 * historial (puede servir para persistir y sacar informacion de como navegan los usuarios en el sitio).
 *
 * Tags: Web Flow, Pages, Estadisticas, Historial.
 * 
 * TODO: me gustaria usar la historia tambien para persistira y poder sacar como navega cada usuario, que paginas vio, etc.
 */

class PageHistory {

   private $history = array(); // Donde se guarda la info de las paginas visitadas.

   public static function getInstance()
   {
      $instance = NULL;
      if ( !YuppSession::contains("_page_history_singleton_instance") )
      {
      	$instance = new PageHistory();
         YuppSession::set("_page_history_singleton_instance", $instance);
      }
      else
      {
      	$instance = YuppSession::get("_page_history_singleton_instance");
      }

      return $instance;
   }

   private function __construct() {}

   // --------------------------------------------------------------------------------------------------------
   // Se definen metodos estaticos para que la llamada sea mucho mas simple, PageHistory::push( 'mipagina' ).
   // Si no tengo que andar creando instancias...
   //
   public static function pop()
   {
      $ph = PageHistory::getInstance();
      return $ph->_pop();
   }

   private function _pop()
   {
      return array_pop( $this->history );
   }


   // No necesito mas que el pageId...
   // Ademas en principio no se como va a ser la representacion de la pagina.
   // Ademas no quiero acoplar esto con la representacion de la pagina.
   // TODo: podria guardar info de estado junto con el pageId: timestamp, url, ip number, etc (todo el request podria guardar!!!).
   public static function push( $pageId )
   {
      $ph = PageHistory::getInstance();
      $ph->_push();
   }

   private function _push( $pageId )
   {
      $this->history[] = $pageId;

       // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
       YuppSession::set("_page_history_singleton_instance", $this); // actualizo la variable en la session...
   }

   // Otras funciones de obtencion y busqueda.... TODO.
}
?>