<?php

/**
 * Este archivo contiene la interfaz que deben implementar las estrategias de carga de datos.
 * 
 * Created on 29/03/2008
 * Modified on 13/06/2010
 * 
 * @name core.persistent.POLoader.interface.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.9.0
 * @package core.persistent
 */

/**
 * Implementada por las estrategias de carga de instancias de Persistentobjects desde la base.
 */
interface POLoader {

   /**
    * Carga las intancias asociadas correspondientes al atributo hasMany $attr del objeto $obj ya dentro del objeto.
    * @param PersistenObject $obj es el objeto que tiene la asociacion hasMany a cargar.
    * @param String $attr es el nombre de la asociacion hasMany de $obj a cargar.
    */
   //public function getMany( &$obj, $attr );
   public function getMany( $obj, $attr );

   /**
    * Devuelve la instancia de la clase $clazz con identificador $id desde la base;
    * @param Class $clazz es la clase del objeto que se quiere cargar.
    * @param Integer $id es el identificador del objetoq que se quiere cargar.
    * @return PersistentObject la instancia de $clazz con el identificador $id, si es que existe, si no, NULL.
    */
   public function get( $clazz, $id );

}

?>
