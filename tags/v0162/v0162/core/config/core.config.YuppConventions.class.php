<?php

/**
 * Clase que implementa las convenciones de Yupp
 */

class YuppConventions {

   function YuppConventions() {
   }
    
   
   /**
    * @return nombre de la columna de la tabla que va a hacer referencia a una
    *  tabla de una superclase, sirve para MTI donde se inyectan atributos
    *  para hacer dichas referencias.
    * @param $superclassName nombre de la clase a la que se quiere hacer referencia.
    */
   public static function superclassRefName( $superclassName )
   {
   	return "super_id_" . $superclassName; //strtolower($superclassName); // TODO: si lo paso a lower, luego puedo no obtener el nombre exacto.
   }
   
   /**
    * Operacion inversa de superclassRefName, para obtener el nombre de la clase.
    */
   public static function superclassFromRefName( $ref )
   {
      return substr($ref, 9); // le saco el "super_id_"
      
   }
   
   /**
    * Verifica si un nombre de un atributo es un nombre de referencia.
    */
   public static function isRefName( $ref )
   {
   	return String::startsWith($ref, "super_id_");
   }
   
   
   public static function getModelPath( $component )
   {
   	return "./components/$component/model/";
   }
   
    
   /**
    * Nombre de la tabla que almacena una instancia de un objeto persistente.
    * Si tiene "withTable", elige ese, si no, toma el nombre de la clase normalizado como nombre de la tabla.
    * @param PersistentObject $ins Instancia de la cual derivar el nombre de la tabla en la base de datos.
    */
   public static function tableName( $instance_or_class )
   {
      $ins = $instance_or_class;
      if ( !is_object($ins) ) $ins = new $instance_or_class(array(), true); // Si no es instancia, es clase, creo una instancia de esa clase.
      if ( !is_a($ins, 'PersistentObject') ) throw new Exception("La instancia debe ser de PO y es " . gettype($ins));
      
      // FIXME: en pila de lados tengo que crear una instancia para poder llamar a este metodo, 
      // porque no mejor hacer que pueda recibir tambien el nombre de la clase, y en ese caso, 
      // resuelve la tabla como nombre de clase, sin considerar withTable, o directamente crea 
      // la instancia internamente. Permitiendo tambien pasarle una instancia, o sea, ambas opciones.
      
      // FIXME: SI NO TIENE WIHT TABLE TENGO QUE VER POR EL NOMBRE DE LA CLASE, PERO ME VIENE PersistentObject,
      // TENGO CAPAZ QUE SETEARLE EL WITH TABLE A MANO SI NO LO TIENE SETEADO!! NOOOOO!!!
      
      // Si no tiene withTable, tengo que crear el nombre de la tabla a partir del nombre de la clase.
      // Si no tiene withTable, quiere decir que en ninguna superclase de ella se define, entonces tengo que
      // obtener la superclase de nivel 1 y el nombre de la tabla se saca de el nombre de esa clase.

      if ( $ins->getWithTable() != NULL && strcmp($ins->getWithTable(), "") != 0 ) // Me aseguro que haya algo.
      {
         $tableName = $ins->getWithTable();
      }
      else
      {
      	$superclaseNivel1 = $ins->getClass();
         while ( ($parent = get_parent_class($superclaseNivel1)) !== 'PersistentObject' )
         {
         	$superclaseNivel1 = $parent;
         }
         
         $tableName = $superclaseNivel1;
         
         //echo "Clase nivel 1: $superclaseNivel1 <br />";
      }

      // Filtro...
      $tableName = DatabaseNormalization::table( $tableName ); // TODO: La funcion de normalizacion esta deberia estar en un core.basic.String.

      return $tableName;
      
   } // tableName
   
   /**
    * Nombre de la tabla que almacena informacion sobre la relacion entre 2 instancias de PO.
    * El nombre de la tabla de la relacion es el nombre derivado de la instancia1 concatenado
    * al nombre del atributo que apunta a la instancia 2, concatenado al nombre derivador de
    * la instancia 2.
    * @param PersistentObject $ins1 Instancia duenia de la asociacion.
    * @param String Atributo de $ins1 que apunta a $ins2.
    * @param PersistentObject $ins2 Instancia hija en la asociacion.
    */
   public static function relTableName( PersistentObject $ins1, $inst1Attr, PersistentObject $ins2 )
   {
      // Problema: si el atributo pertenece a C1, y $ins1 es instancia de G1,
      //           la tabla que se genera para hasMany a UnaClase es "gs_unaclase_"
      //           y deberia ser "cs_unaclase_", esto es un problema porque si cargo 
      //           una instancia de C1 no tiene acceso a sus hasMany "UnaClase".

      // TODO: esta es una solucion rapida al problema, hay que mejorarla.
      
      // Esta solucion intenta buscar cual es la clase en la que se declara el atributo hasMany
      // para el que se quiere generar la tabla intermedia de referencias, si no la encuentra, 
      // es que el atributo hasMany se declaro en $ins1.
      
      $classes = ModelUtils::getAllAncestorsOf( $ins1->getClass() );
      
      //Logger::struct( $classes, "Superclases de " . $ins1->getClass() );
      
      $instConElAtributoHasMany = $ins1; // En ppio pienso que la instancia es la que tiene el atributo masMany.
      foreach ( $classes as $aclass )
      {
      	$ins = new $aclass();
         if ( $ins->hasManyOfThis( $ins2->getClass() ) )
         {
            //Logger::getInstance()->log("TIENE MANY DE " . $ins2->getClass());
            $instConElAtributoHasMany = $ins;
            break;
         }
         
         //Logger::struct( $ins, "Instancia de $aclass" );
      }
      
  
/*    
      if ( $ins1->getWithTable() != NULL && strcmp($ins1->getWithTable(), "") != 0 ) // Me aseguro que haya algo.
      {
         $tableName1 = $ins1->getWithTable();
      }

      if ( $ins2->getWithTable() != NULL && strcmp($ins2->getWithTable(), "") != 0 ) // Me aseguro que haya algo.
      {
         $tableName2 = $ins2->getWithTable();
      }
      
      // FIXME: si no tiene withTable seteado deberia tomar el nombre de la clase, como se hace en "tableName()".

      $tableName1 = DatabaseNormalization::table( $tableName1 );
      $tableName2 = DatabaseNormalization::table( $tableName2 );
*/

      /*
      if ($instConElAtributoHasMany->getClass() === $ins1->getClass())
         $tableName1 = self::tableName( $ins1 );
      else
         $tableName1 = self::tableName( $instConElAtributoHasMany );
      */
      
      // Esto es igual a hacer lo anterior que esta comentado:
      $tableName1 = self::tableName( $instConElAtributoHasMany );
      
      $tableName2 = self::tableName( $ins2 );
      

      // TODO: Normalizar $inst1Attr ?

      return $tableName1 . "_" . $inst1Attr . "_" . $tableName2; // owner_child
   }
}
?>