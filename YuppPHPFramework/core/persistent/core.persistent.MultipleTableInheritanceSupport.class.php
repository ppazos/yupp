<?php

/**
 * Esta clase es la que ofrece el soporte a la persistencia de estructuras de herencia cuando se guardan en tablas distintas.
 * 
 * Created on 15/12/2007
 * Modified on 13/06/2010
 * 
 * @name core.persistent.MultipleTableInheritanceSupport.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.9.0
 * @package core.persistent
 */
class MultipleTableInheritanceSupport {

   /**
    * Devuelve una lista de todas las superclases de $class que son generadoras de tablas, es decir, 
    * la superclase de nivel 1 y las subclases de esa que tengan definido el withTable y sea distinto 
    * al de la clase de nivel 1 (si la de nivel 1 lo tiene definido).
    *
    * @param $class nombre de la clase para la cual se piden las superclases que generen tablas.
    */
   public static function superclassesThatGenerateTables( $class )
   {
      $res = array();
      
      $superClasses = ModelUtils::getAllAncestorsOf( $class ); // Si class es de nivel 1, esto es vacio.
                                                               // La primer clase es la de nivel 1, y esta ordenado hasta la ultima subclase en el ultimo lugar.
      
      // FIXME: aca las instancias se generan solo para obtener el withTable, no necesito que se haga todo el proceso del construct del PO, 
      // tendria que tener un construct simple que no haga nada, solo instanciar la clase y que esa instancia levante una bandera de que es
      // simple y que si se intenta hacer algo como un setXXX tire un warning de que es para proceso interno no para usar el objeto.
      
      if (count($superClasses) > 0)
      {
         //Logger::getInstance()->log( "LEVEL 1 ($level1SuperClass) WITH TABLE: " . $level1WithTable );
      
         $superSuperClass = array_shift($superClasses);
         
         array_unshift( $res, $superSuperClass ); // Agrega al principio, la clase de nivel 1 siempre genera tabla.
         
         foreach ( $superClasses as $superClass )
         {
            $superSuperClassIns = new $superSuperClass(array(), true);
            $superClassIns = new $superClass(array(), true);
            
            //Logger::getInstance()->log( "($superSuperClass) WITH TABLE: " . $superSuperClassIns->getWithTable() );
            //Logger::getInstance()->log( "($superClass) WITH TABLE: " . $superClassIns->getWithTable() );
            //Logger::getInstance()->log( "MAPPED ON SAME TABLE: ($superClass, $superSuperClass) " . PersistentManager::isMappedOnSameTable( $superClass, $superSuperClass ) );
            
            if ( $superClassIns->getWithTable() !== $superSuperClassIns->getWithTable() ) // pueden ser NULL.
            {
               array_unshift( $res, $superClass ); // Agrega al principio.
            }
            
            $superSuperClass = $superClass;
         }
      
         $lastSuperClass = $res[0]; // (**) No quiero que $class este en la misma tabla que la ultima superclase que genera tabla.
         if ( PersistentManager::isMappedOnSameTable( $lastSuperClass, $class ) )
         {
            array_shift($res); // Saca el primer elemento del array.
         }
      }
      
      return $res;
   }

   /**
    * Devuelve la superclase de $class que genera la tabla donde se guarda $class.
    * Si $class es de nivel 1, se retorna $class.
    */
   public static function superclassThatGenerateMyTable( $class )
   {
      $parent = get_parent_class($class);
      if ( $parent === 'PersistentObject' ) return $class;
      
      $classTable = YuppConventions::tableName( $class );
      
      // Tengo que ir para arriba y ver quien es el ultimo que tiene la tabla 
      // igual a la mia, antes de llegar a PO o llegar a otro con distinta tabla.
      
      // Chekeo si ya la primer superclase se mapea en una tabla distinta, devuelvo $class.
      $superclassTable = YuppConventions::tableName( $parent );
      if ( $classTable != $superclassTable )
      {
         return $class;
      }
      
      // $class y $parent se mapean en la misma tabla.
      $prevClassToIterate = $parent;
      $classToIterate = get_parent_class($parent); // Puede ser PO.
      $found = false;
      while (!$found && $classToIterate !== 'PersistentObject')
      {
         $superclassTable = YuppConventions::tableName( $classToIterate );
         
         if ( $classTable != $superclassTable )
         {
            $found = true; // Quiero la clase que genera la tabla anterior.
         }
         else
         {
            $prevClassToIterate = $classToIterate;
            $classToIterate = get_parent_class($classToIterate);
         }
      }
      
      return $prevClassToIterate;
      
   } // superclassThatGenerateMyTable

   /**
    * Si tengo una estructura de herencia, me devuelve un mapeo de las superclases y sus subclases que se mapean en la misma tabla.
    * @param $levelOneClass es una subclase directa de PO.
    */
   public static function getMultipleTableInheritanceStructureToGenerateModel( $levelOneClass )
   {  
      $C = ModelUtils::getAllSubclassesOf( $levelOneClass );
      $C[] = $levelOneClass;
      $struct = self::getMultipleTableInheritance( $C );
      return $struct;
   }
   
   /**
    * Se utiliza para obtener una estructura de mapeo de clases de herencia sobre diferentes tablas.
    * Como se utiliza como auxiliar de generateAll la pongo aca, talvez pueda ir en ModelUtils, pero
    * en realidad solo se usa para generar el esquema y para salvar.
    * 
    * @param $inheritanceClasses lista de clases de una estructura de herencia (clase de la instancia
    *        del objeto que se esta manejando y todas sus superclases) (no se asume ningun orden).
    */
   public static function getMultipleTableInheritance( $inheritanceClasses )
   {
      Logger::getInstance()->dal_log("MTI::getMultipleTableInheritance");
      
      // Ahora depende de una aplicacion.
      // La estructura de MTI no pueden establecerse entre clases del modelo de distintas aplicaciones.
      $ctx = YuppContext::getInstance();
      $appName = $ctx->getApp();
    
      // 1. clases y sus subclases
      $e = array(); // array por clave la clase y valor lista de subclases directas de dicha clase
      foreach ($inheritanceClasses as $class)
      {
         // Quiero poner solo las clases que esten en $inheritanceClasses, que pueden 
         // no ser todas las de la estructura de herencia, esto sirve para 
         // implementar getPartialInstancesToSave.
         // TODO: en que caso una clase no es de la estructura de herencia si lo 
         //       que le paso como parametro es solo la estructura de herencia???
         //       Ver lo que le pasan todas las operaciones que invoque a esta getMultipleTableInheritance.
         $sclss = ModelUtils::getSubclassesOf( $class, $appName );
         
         $e[$class] = array_intersect( $inheritanceClasses, $sclss );
      }
      
      // 2. Arma array de clases y lista de subclases que se mapean en la misma tabla.
      $e1 = array();
      foreach ( $e as $class => $subclasses )
      {
         $c_ins = new $class();
         if ( !array_key_exists($class, $e1) || $e1[$class] === NULL ) $e1[$class] = array(); // armo otro array con las subclases que no tienen withTable.
         foreach ($subclasses as $subclass)
         {
            //$sc_ins = new $subclass();
            //echo $subclass . " " . $sc_ins->getWithTable() . "<br />";
            //if ( $sc_ins->getWithTable() === $c_ins->getWithTable() ) $e1[$class][] = $subclass; // solo si los withTable son iguales (o sea, que no lo redefine en la subclase)
            
            if ( PersistentManager::isMappedOnSameTable( $class, $subclass ) )
            {
               $e1[$class][] = $subclass;
               
               //Logger::getInstance()->dal_log("isMapperOnSameTable: $class , $subclass " . __FILE__ . " " . __LINE__ );
            }
         }
      }
      
      //Logger::getInstance()->dal_log("clases y subclases en la misma tabla " . __FILE__ . " " . __LINE__ );
      //Logger::struct( $e1 );
      
      // 3. Todas las keys estan en $inheritanceClasses
      foreach ( $e1 as $class => $sameTableFirstLevelSubclasses )
      {
         //echo "CLASS: $class<br />";
         if ( $sameTableFirstLevelSubclasses !== NULL ) // xq voy poniendo en NULL los que voy moviendo.
         {
            if ( count($sameTableFirstLevelSubclasses) > 0 ) // si no tengo elementos no hago nada xq otro lo pone en null (si es una subclase) o se queda con un array vacio (si es una clase sin subclases)
            {
               $merge = array_merge( $sameTableFirstLevelSubclasses, array()); // copia los valores ???
               foreach ( $sameTableFirstLevelSubclasses as $subclass )
               {
                  $classesToMerge = $e1[$subclass];
                  if ( $classesToMerge !== NULL ) // Si en $inheritanceClasses no tiene todas las clases de la estrcutura de herencia (solo porque quiero algunas clases) esto podria ser null...
                  {
                     $merge = array_merge( $merge, $classesToMerge );
                     $e1[$subclass] = NULL;
                  }
               }
               $e1[$class] = $merge;
            }
         }
      }
      
      /* Esta mal! D, E y B deberian ser NULL tambien.
Array
(
    [B] => 
    [C] => Array
        (
            [0] => E
        )

    [D] => Array
        (
        )

    [E] => Array
        (
        )

    [F] => Array
        (
        )

    [G] => Array
        (
        )

    [A] => Array
        (
            [0] => B
            [1] => D
        )
)
       */
      
      //$sol = array_filter( $e1, 'filter_not_null' );
      // no me deja hacer el filter ... lo hago a mano...
      $sol = array();
      foreach ( $e1 as $class => $subclasses )
      {
         if ( $subclasses !== NULL ) $sol[$class] = $subclasses;
      }
      
      return $sol;
      
   } // getMultipleTableInheritance
   
   /**
    * Dada una estructura de herencia multiple (dada por getMultipleTableInheritanceStructureToGenerateModel)
    */
   public static function getPartialInstantes( $struct )
   {
      $sol = array();
      
      foreach ($struct as $class => $subclassesOnSameTable)
      {
         $c_ins = new $class(); // FIXME: supongo que ya tiene withTable, luego veo el caso que no se le ponga WT a la superclase...
         // FIXME: como tambien tiene los atributos de las superclases y como van en otra tabla, hay que sacarlos.
         
         foreach ( $subclassesOnSameTable as $subclass )
         { 
            $sc_ins = new $subclass(); // Para setear los atributos.
            $props = $sc_ins->getAttributeTypes();
            $hone  = $sc_ins->getHasOne();
            $hmany = $sc_ins->getHasMany();
            $constraints = $sc_ins->getConstraints();
            foreach( $props as $name => $type ) $c_ins->addAttribute($name, $type);
            foreach( $hone  as $name => $type ) $c_ins->addHasOne($name, $type);
            foreach( $hmany as $name => $type ) $c_ins->addHasMany($name, $type);
            foreach( $constraints as $attr => $constraintList ) $c_ins->addConstraints($attr, $constraintList);
         }
         
         $parent_class = get_parent_class($c_ins);
         if ( $parent_class !== 'PersistentObject' )
         {
            // La superclase de c_ins se mapea en otra tabla, saco esos atributos...
            $suc_ins = new $parent_class();
            $c_ins = PersistentObject::less($c_ins, $suc_ins); // Saco los atributos de la superclase
         }
         
         $sol[] = $c_ins;
      }
      
      return $sol;
   }
   
   /**
    * Hace el merge entre instancias parciales de la misma clase. La idea
    * es usarlo en el "get" para cargar IPs e irlas mergeando para armar 
    * la instancia total.
    * Probar: deberia ser capaz de agarrar el resultado de getPartialInstancesToSave (de 2 en 2) y armar la instancia exactamente como era.
    * @param $sc_ins es una instancia de una superclase de $c_ins.
    * @param $c_ins es uan instancia de una subclase de $sc_ins.
    */
   /*
   public static function mergePartialInstances( $sc_ins, $c_ins )
   {
      // TODO
      // La clase resultante deberia ser instancia de la subclase $c_ins
   }
   */
   
   /**
    * Dada una instancia de PO, devuelve una estructura con las clases que se deben guardar 
    * en distintas tablas y los atrbibutos de cada una con sus respectivos valores.
    */
   public static function getPartialInstancesToSave( $po_ins )
   {
      Logger::getInstance()->dal_log("MTI::getPartialInstancesToSave ". $po_ins->getClass());
      //Logger::struct( $po_ins, __FILE__ . ".getPartialInstancesToSave " . __LINE__ );
      
      // TODO: (performance) si el objeto no representa un MTI no deberia hacerse todo el trabajo de copiar cada atributo del objeto,
      //       eso deberia verificarse antes, y de no ser un MTI, devolver nomas un array con el objeto entrante.


      // Para simplificar y no tener que hacer aGet sobre po_ins
      $values = $po_ins->getAttributeValues();


      //$superclasses = ModelUtils::getAllAncestorsOf( $po_ins->getClass() ); // puede ser en cualquier orden!
      $superclasses = ModelUtils::getAllAncestorsOf( $values['class'] ); // puede ser en cualquier orden!
      
      
      /*
      // Quiero la clase de nivel 1
      $level1Class = NULL;
      foreach ( $superclasses as $class )
      {
         if ( get_parent_class( $class ) == 'PersistentObject' )
         {
            $level1Class = $class;
            break; // salir del foreach
         }
      }
      $struct = self::getMultipleTableInheritanceStructureToGenerateModel( array($level1Class) );
      */
      
      // Lo anterior es lo mismo que hacer esto: 
      // NO! SERIA LO MISMO SI LA INSTANCIA QUE ME PASAN ES DE LA ULTIMA CLASE DE LA ESTRUCTURA DE HERENCIA.
      // CORRECCION, esta bien porque hace getClass, y obtiene la clase real que es la ultima de la estructura!!!!
      //$superclasses[] = $po_ins->getClass();
      $superclasses[] = $values['class'];
      
      // Mapa de clases y subclases que se mapean en la misma tabla.
      $struct = self::getMultipleTableInheritance( $superclasses );
      
      //Logger::getInstance()->dal_log("getMultipleTableInheritance (son las subclases en la misma tabla) " . __FILE__ . " " . __LINE__ );
      //Logger::struct( $struct );
      
      // TODO: Partial Instances no considera valores, tengo que setear los valores a mano a partir de los valores de po_ins.
      $partialInstances = self::getPartialInstantes( $struct ); // Instancias de las clases en $superclasses que solo tienen los atributos que van en cada tabla. Cada clase de estas se mapea directamente con una tabla.
      
      foreach ($partialInstances as $partialInstance)
      {
         $attrs_values = $partialInstance->getAttributeTypes(); // El tipo no lo uso para nada, solo necesito la declaracion de atributos.
         foreach ($attrs_values as $attr => $type)
         {
            //echo $partialInstance->getClass() . " " . $po_ins->getClass() . " $attr<br />";
            
            //if ( $partialInstance->getClass() === $po_ins->getClass() || // Seteo atributos inyectados tambien, xq son de esta instancia!
            if ( $partialInstance->getClass() === $values['class'] || // Seteo atributos inyectados tambien, xq son de esta instancia!
                 !PersistentObject::isInyectedAttribute($attr) // Solo setea valores de atributos no inyectados
               ) 
            {
               // ===============================================================================
               // aGet tiene cierta complejidad pidiendo values directamente es mas rapido
               //$partialInstance->aSet($attr, $po_ins->aGet($attr));
               $partialInstance->aSet($attr, $values[$attr]); // PO garantiza que vienen valores para todos los indices, aunque sean NULL, por eso no es necesario hacer un isset($values[$attr])
            }
         }
         
         // El deleted, si la instancia a salvar esta deleted, todos los registros deben estarlo!
         //$partialInstance->setDeleted( $po_ins->getDeleted() );
         $partialInstance->setDeleted($values['deleted']);
         
      } // getPartialInstancesToSave
      
      return $partialInstances; // derecho para salvar cada uno usando save_object!!!
   }
   
   /**
    * Retorna true si el objeto no es PO de nivel 1 (o mapeada en la misma tabla que la de nivel 1) 
    * y se mapea en otra tabla que alguno de sus padres.
    */
   public static function isMTISubclassInstance( $obj )
   {
      // No alcanza que la superclase tenga un withTable distinto, tengo que buscar alguna superclase con withTable distinto.
      //$parentClass = get_parent_class($obj);
      //$pins = new $parentClass(array(), true);
      //return ($parentClass !== 'PersistentObject' && $obj->getWithTable() !== $pins->getWithTable());
      
      // FIXME: esto chekea hacia arriba, pero si le paso una clase muy arriba, tendria que fijarme 
      // si para abajo hay alguna subclase en otra tabla. El tema que para abajo puede haber una subclase
      // que se mapea en otra tabla. Pero como dice el contrato, para clases de nivel 1 o mapeadas en el 
      // nivel 1 no funciona.
      
      $found = false; // Si se encontro un withTable distinto al del objeto.
      $objToIterate = $obj;
      while (!$found)
      {
         $parentClass = get_parent_class($objToIterate);
         $pcIns = new $parentClass(array(), true);
         
         if ($parentClass !== 'PersistentObject')
         {
            //Logger::getInstance()->pm_log("Caso1: padre no es PO, padre $parentClass " . __FILE__ ." ". __LINE__);
            if ($obj->getWithTable() !== $pcIns->getWithTable()) $found = true;
         }
         else
         {
            //Logger::getInstance()->pm_log("Caso2: padre es PO " . __FILE__ ." ". __LINE__);
            break; // Si llego a PO sin encontrar, tengo que parar el loop.
         }
         
         $objToIterate = $pcIns;
      }
      
      return $found;
   }
}
?>