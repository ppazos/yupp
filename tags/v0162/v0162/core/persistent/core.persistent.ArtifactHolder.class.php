<?php
 /**
 * Este archivo contiene la clase singleton utilizada para almacenar objetos que se cargaron 
 * de la base de datos, ante la carga de nuevos objetos actua como cache.
 * 
 * Created on 07/01/2008
 * Modified on 30/05/2008
 * 
 * @name core.persistent.ArtifactHolder.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.1.0
 * @package core.persistent
 * 
 * @link ... (PHPDoc)
 * 
 */


// TODO: corregir identacion.


/**
 * Clase que implementa un contenedor de objetos donde se registran a medida que se van cargando.
 * @package core.persistent
 * @subpackage classes
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
      Logger::artholder_log("ArtifactHolder.addModel " . get_class($obj));

      $class = get_class($obj);
      $id = $obj->getId();

      //print_r( $obj );

      //Logger::artholder_log("ArtifactHolder.addModel " . $class . " " . $id);

      if ( !$this->model[$class] ) $this->model[$class] = array();

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
      Logger::artholder_log("ArtifactHolder.getModel " . $class . " " . $id);
   	  if ( !$this->model[$class] ) return NULL;
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
      Logger::artholder_log("ArtifactHolder.existsModel " . $class . " " . $id);
      //print_r( $this->model );

   	  return ( $this->getModel($class, $id) != NULL );
   }

}

?>
