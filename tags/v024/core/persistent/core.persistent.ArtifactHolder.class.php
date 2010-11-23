<?php

/**
 * Clase que implementa un contenedor de objetos donde se registran a medida que se van cargando.
 * 
 * Created on 07/01/2008
 * Modified on 13/06/2010
 * 
 * @name core.persistent.ArtifactHolder.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.9.0
 * @package core.persistent
 * 
 */

class ArtifactHolder {

   private static $instance = NULL;

   public static function getInstance()
   {
      if ( self::$instance == NULL ) self::$instance = new ArtifactHolder();
      return self::$instance;
   }

   // primer clave = class, valor = coleccion, 2da clave = id
   private $model = array();

   /**
    * Registra el objeto cargado en el holder.
    * @pre El objeto debe tener su identificador.
    * @param PersistentObject $obj el objeto a registrar.
    */
   public function addModel( PersistentObject &$obj )
   {
      Logger::getInstance()->artholder_log("ArtifactHolder.addModel " . get_class($obj));

      $class = get_class($obj);
      $id = $obj->getId();

      //Logger::artholder_log("ArtifactHolder.addModel " . $class . " " . $id);
      if ( !array_key_exists($class, $this->model) || !$this->model[$class] ) $this->model[$class] = array();

      $this->model[$class][$id] = $obj;
   }


   /**
    * Obtiene un elemento previamente registrado, si es que existe, si no retorna NULL.
    * @param class $class la clase del objeto que se pide.
    * @param integer $id identificador del objeto pedido.
    * @return PersistentObject si el objeto existe lo retorna, si no retorna NULL.
    */
   public function getModel( $class, $id )
   {
      Logger::getInstance()->artholder_log("ArtifactHolder.getModel " . $class . " " . $id);
      
      if ( !isset($this->model[$class]) ) return NULL;
      if ( !isset($this->model[$class][$id]) ) return NULL;
      return $this->model[$class][$id]; // VERIFY: Si no tengo un objeto con $id me tira null o una excepcion de que no existe la key?
   }

   /**
    * Verifica si el objeto esta registrado.
    * @param class $class la clase del objeto que se pide.
    * @param integer $id identificador del objeto pedido.
    * @return boolean TRUE si el objeto existe, si no retorna FALSE.
    */
   public function existsModel( $class, $id )
   {
      Logger::getInstance()->artholder_log("ArtifactHolder.existsModel " . $class . " " . $id);

      return ( $this->getModel($class, $id) != NULL );
   }

}

?>
