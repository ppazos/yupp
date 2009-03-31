<?php
/**
 * Este archivo contiene la estrategia de carga de datos perezosa, 
 * la cual no carga elementos assciados a no ser que sean pedidos explicitamente.
 * 
 * Created on 29/03/2008
 * Modified on 30/05/2008
 * 
 * @name core.persistent.LazyLoadStrategy.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.1.0
 * @package core.persistent
 * 
 * @link ... (PHPDoc)
 */

/**
 * Clase que implementa la carga de datos de forma perezosa. Esta clase no implementa la logica de carga, 
 * dice que funciones y en que orden se llaman a las funciones de carga del PersistentManager.
 * @package core.persistent
 * @subpackage classes
 */
class LazyLoadStrategy implements POLoader {

    private $manager; // PersistentManager

    /**
     * Se le setea el manager que tiene definidas las funciones necesarias para cargar objetos.
     * Se puede ver esto como una instancia del patron IOC.
     * @param PersistentManager $manager
     */
    public function setManager( $manager ) {
    	 $this->manager = $manager;
    }

   /**
    * Carga las intancias asociadas correspondientes al atributo hasMany $attr del objeto $obj ya dentro del objeto.
    * @param PersistenObject $obj es el objeto que tiene la asociacion hasMany a cargar.
    * @param String $attr es el nombre de la asociacion hasMany de $obj a cargar.
    */
   public function getMany( &$obj, $attr )
   {
   	$this->manager->get_many_assoc_lazy( &$obj, $attr );
   }

   /**
    * Devuelve la instancia de la clase $clazz con identificador $id desde la base;
    * @param Class $clazz es la clase del objeto que se quiere cargar.
    * @param Integer $id es el identificador del objetoq que se quiere cargar.
    * @return PersistentObject la instancia de $clazz con el identificador $id, si es que existe, si no, NULL.
    */
   public function get( $clazz, $id )
   {
      return $this->manager->get_object( $clazz, $id );
   }
}
?>