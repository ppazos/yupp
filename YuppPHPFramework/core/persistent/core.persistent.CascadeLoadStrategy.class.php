<?php

/**
 * Clase que implementa la carga de datos en cascada. Esta clase no implementa la logica de carga, 
 * dice que funciones y en que orden se llaman a las funciones de carga del PersistentManager.
 * 
 * Created on 29/03/2008
 * Modified on 30/05/2008
 * 
 * @name core.persistent.CascadeLoadStrategy.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.9.0
 * @package core.persistent
 * 
 */
YuppLoader::loadInterface( "core.persistent", "POLoader" );

class CascadeLoadStrategy implements POLoader {

    private $manager; // PersistentManager

    /**
     * Se le setea el manager que tiene definidas las funciones necesarias para cargar objetos.
     * Se puede ver esto como una instancia del patron IOC.
     * @param PersistentManager $manager
     */
    public function setManager( $manager )
    {
       $this->manager = $manager;
    }

   /**
    * Carga las intancias asociadas correspondientes al atributo hasMany $attr del objeto $obj ya dentro del objeto.
    * @param PersistenObject $obj es el objeto que tiene la asociacion hasMany a cargar.
    * @param String $attr es el nombre de la asociacion hasMany de $obj a cargar.
    */
   public function getMany( $obj, $attr )
   {
      $this->manager->get_many_assoc_lazy( $obj, $attr ); // Carga los objetos en la lista del atributo, pero sin cargar asociaciones

      // Para cada objeto de la lista carga sus asociaciones en cascada...
      $objList = $obj->{"get".$attr}(); // FIXME: usar aGet($attr)

      foreach ($objList as $hm_obj)
      {
         $this->getCascadeAssocs( $hm_obj );

         // VERIFY: La verificacion de ArtHolder se hace en get_many_assoc_lazy y no la tengo que hacer aca?

      } // foreach objList
   }

   /**
    * Devuelve la instancia de la clase $clazz con identificador $id desde la base;
    * @param Class $clazz es la clase del objeto que se quiere cargar.
    * @param Integer $id es el identificador del objetoq que se quiere cargar.
    * @return PersistentObject la instancia de $clazz con el identificador $id, si es que existe, si no, NULL.
    */
   public function get( $clazz, $id )
   {
      Logger::getInstance()->pm_log("PO CascadeLoad::get ". $clazz."(".$id.") : " . __FILE__."@". __LINE__);
      
      // manager->get_object
      $obj = $this->manager->get_object( $clazz, $id );

      $this->getCascadeAssocs( $obj );
      return $obj;
   }


   /**
    * Metodo auxiliar para cargar los objetos asociados en cascada, tanto los hasOne como los hasMany, 
    * verificando previamente si no fueron ya cargados.
    * @param PersistentObject $obj el objeto al que hay que cargarle los objetos asociados.
    */
   private function getCascadeAssocs( $obj )
   {
      // Para saber si estaba limpia previamente, para poder limpiar lo que esta operacion ensucia (dirty bits)
      $wasClean = $obj->isClean();
      
      
      // TODO: Verificar si para los objetos en hasOne, sus asociaciones son cargadas en cascada.
      
      // Para cada objeto hasOne, lo trae.
      // Para el objeto hago get para hasOne y getMany para los hasMany.
      $ho_attrs = $obj->getHasOne();
      foreach( $ho_attrs as $attr => $assoc_class )
      {
         // attr = "email_id" ?

         $ho_instance = new $assoc_class();
         $hasOneAttr  = DatabaseNormalization::getSimpleAssocName( $attr ); // email
         $assoc_id    = $ho_instance->aGet( $attr );

         $assoc_obj = NULL;
         if ( ArtifactHolder::getInstance()->existsModel( $assoc_class, $assoc_id ) ) // Si ya esta cargado...
         {
            $assoc_obj = ArtifactHolder::getInstance()->getModel( $assoc_class, $assoc_id );
         }
         else
         {
            $assoc_obj   = $this->get( $assoc_class, $assoc_id );
            ArtifactHolder::getInstance()->addModel( $assoc_obj );
         }

         //$obj->{"set".$attr}( $assoc_obj );
         $obj->aSet($attr, $assoc_obj);
      }

      // Para cada objeto hasMany, lo trae
      // Para el objeto hago get para hasOne y getMany para los hasMany.
      $hm_attrs = $obj->getHasMany();
      foreach( $hm_attrs as $attr => $class )
      {
         $this->getMany( $obj, $attr ); // Carga los elementos del atributo en la clase.
      }
      
      // TODO: si carga en cascada, entonces la instancia de obj y sus clases asociadas
      //       se cargan todas en el mismo momento, por lo que no habria nada sucio,
      //       y seria innecesario verificar si estaba sucia antes de la carga.
      // Solo limpia si la clase estaba limpia antes de la operacion
      if ( $wasClean )
         $obj->resetDirty(); // Apaga las banderas que se prendieron en la carga
   }
}
?>