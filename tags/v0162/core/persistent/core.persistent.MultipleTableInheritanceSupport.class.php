<?php

class MultipleTableInheritanceSupport {

   /**
    * Devuelve una lista de todas las superclases de $class que son generadoras de tablas, es decir, 
    * la superclase de nivel 1 y las subclases de esa que tengan definido el withTable y sea distinto 
    * al de la clase de nivel 1 (si la de nivel 1 lo tiene definido).
    * Obs: no se devuelve la clase $class, son solo las superclases. (porque esta operacion se 
    * utiliza en el inyectado de los super_id_xx). Se hizo especialmente para el constructor de
    * POs cuando inyecta los super_id_xx.
    * @param $class nombre de la clase para la cual se piden las superclases que generen tablas.
    */
   public static function superclassesThatGenerateTables( $class )
   {
      $res = array();
      
   	$superClasses = ModelUtils::getAllAncestorsOf( $class ); // Si class es de nivel 1, esto es vacio.
                                                               // La primer clase es la de nivel 1, y esta ordenado hasta la ultima subclase en el ultimo lugar.
      
      //Logger::struct( $superClasses );
      
      // FIXME: aca las instancias se generan solo para obtener el withTable, no necesito que se haga todo el proceso del construct del PO, 
      // tendria que tener un construct simple que no haga nada, solo instanciar la clase y que esa instancia levante una bandera de que es
      // simple y que si se intenta hacer algo como un setXXX tire un warning de que es para proceso interno no para usar el objeto.
      
      if ( count( $superClasses ) > 0 )
      {
      	//$level1SuperClass = array_shift($superClasses); // Saca el primer elemento del array.
         //$level1SuperClassIns = new $level1SuperClass(array(), true);
         //$level1WithTable = $level1SuperClassIns->getWithTable();
         
         //Logger::getInstance()->log( "LEVEL 1 ($level1SuperClass) WITH TABLE: " . $level1WithTable );
      
         $superSuperClass = array_shift($superClasses);
         
         array_unshift( $res, $superSuperClass ); // Agrega al principio, la clase de nivel 1 siempre genera tabla.
         
         foreach ( $superClasses as $superClass )
         {
            $superSuperClassIns = new $superSuperClass(array(), true);
            $superClassIns = new $superClass(array(), true);
            
            //Logger::getInstance()->log( "($superSuperClass) WITH TABLE: " . $superSuperClassIns->getWithTable() );
            //Logger::getInstance()->log( "($superClass) WITH TABLE: " . $superClassIns->getWithTable() );
            
            // Hay que verificar que genere tabla y ademas que la clase $class no este en la tabla que genera, 
            // no quiero esa tabla, porque esta funcion se usa para generar los super_id_zzz y para la tabla 
            // donde se guarda esa clase no necesito generar ese atributo (** se hace abajo)
            
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
      
      //Logger::struct( $res );
      
      return $res;
   }


   /**
    * Devuelve la superclase de $class que genera la tabla donde se guarda $class.
    * Si $class es de nivel 1, se retorna $class.
    */
   public static function superclassThatGenerateMyTable( $class )
   {
      $parent = get_parent_class($class);
   	if ( $parent === PersistentObject ) return $class;
      
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
      while (!$found && $classToIterate != PersistentObject)
      {
         $superclassTable = YuppConventions::tableName( $classToIterate );
         
         if ( $classTable != $superclassTable )
         {
         	// Quiero la clase que genera la tabla anterior.
            $found = true;
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
    * Como se utiliza como auxiliar de generateAll la pongo aca, talvez pueda ir en ModelUtils, pero en realidad solo se usa para generar el esquema.
    * @param $inheritanceClasses lista de clases de una estructura de herencia (no se asume ningun orden).
    */
   public static function getMultipleTableInheritance( $inheritanceClasses )
   {
      // 1.
      $e = array(); // array por clave la clase y valor lista de subclases directas de dicha clase
      foreach ($inheritanceClasses as $class)
      {
         //$e[$class] = ModelUtils::getSubclassesOf( $class );
         
         // Quiero poner solo las clases que esten en $inheritanceClasses, que pueden 
         // no ser todas las de la estructura de herencia, esto sirve para 
         // implementar getPartialInstancesToSave.
         $sclss = ModelUtils::getSubclassesOf( $class );
         $e[$class] = array_intersect( $inheritanceClasses, $sclss );
      }
      
      // 2.
      $e1 = array();
      foreach ( $e as $class => $subclasses )
      {
         $c_ins = new $class();
         if ( $e1[$class] == NULL ) $e1[$class] = array(); // armo otro array con las subclases que no tienen withTable.
         foreach ($subclasses as $subclass)
         {
            $sc_ins = new $subclass();
            
            //echo $subclass . " " . $sc_ins->getWithTable() . "<br />";
            
            if ( $sc_ins->getWithTable() === $c_ins->getWithTable() ) $e1[$class][] = $subclass; // solo si los withTable son iguales (o sea, que no lo redefine en la subclase)
         }
      }
      
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
//                  echo "SUBCLASS: $subclass<br/>";
                  $classesToMerge = $e1[$subclass];
                  if ( $classesToMerge !== NULL ) // Si en $inheritanceClasses no tiene todas las clases de la estrcutura de herencia (solo porque quiero algunas clases) esto podria ser null...
                  {
                     $merge = array_merge( $merge, $classesToMerge );
                     //foreach ( $classesToMerge as $classToMerge )
                     //{
                        //echo "SUBCLASS: $subclass<br />";
                     //}
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
         
//            echo "<pre> SUBCLASSES ON SAME TABLE: $class <br />";
//            print_r( $subclassesOnSameTable );
//            echo "</pre>";
         
         foreach ( $subclassesOnSameTable as $subclass )
         { 
            //echo "SUBC: $subclass<br />";
            
            $sc_ins = new $subclass(); // Para setear los atributos.
            $props = $sc_ins->getAttributeTypes();
            $hone  = $sc_ins->getHasOne();
            $hmany = $sc_ins->getHasMany();
            $constraints = $sc_ins->getConstraints();
            foreach( $constraints as $attr => $constraintList ) $c_ins->addConstraints($attr, $constraintList);
            foreach( $props as $name => $type ) $c_ins->addAttribute($name, $type);
            foreach( $hone  as $name => $type ) $c_ins->addHasOne($name, $type);
            foreach( $hmany as $name => $type ) $c_ins->addHasMany($name, $type);
         }
         
         $parent_class = get_parent_class($c_ins);
         if ( $parent_class !== PersistentObject )
         {
            // Se inyecta en el constructor...
            //$c_ins->addAttribute("super_id", Datatypes::INT_NUMBER);
            
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
   public static function mergePartialInstances( $sc_ins, $c_ins )
   {
   	// TODO
      // La clase resultante deberia ser instancia de la subclase $c_ins
   }
   
   
   /**
    * Dada una instancia de PO, devuelve una estructura con las clases que se deben guardar 
    * en distintas tablas y los atrbibutos de cada una con sus respectivos valores.
    * 
    * FIXME: el atrib super_id no va mas, ahora hay un atributo "$multipleTableIds" en PO, que tiene toda la info de los ids de las superclases en sus tablas.
    * Los super_id para un insert deben ser inyectados al generar la clase (se deberia guardar de arriba a abajo!)
    * Para update, tendria que consultar y hacer el update, de arriba a abajo.
    */
   public static function getPartialInstancesToSave( $po_ins )
   {
      Logger::add( Logger::LEVEL_PM, "MTI.getPartialInstancesToSave" );
      
      //Logger::struct( $po_ins, __FILE__ . ".getPartialInstancesToSave " . __LINE__ );
      
      // TODO: (performance) si el objeto no representa un MTI no deberia hacerse todo el trabajo de copiar cada atributo del objeto,
      //       eso deberia verificarse antes, y de no ser un MTI, devolver nomas un array con el objeto entrante.
      

      // ===========================================================================================      
      // ===========================================================================================
      // FIXME: para la instancia parcial C no esta seteado el super_id_A, y viene seteado en G...
      // FIXME: Ni A ni C tienen su "id" seteado tampoco.
      // FIXME: No se si eso deberia hacerse en otra funcion> getPartialInstancesToUpdate.
      // FIXME: Otro, G no tiene super_id_C.
      // ===========================================================================================
      // ===========================================================================================

      
   	$superclasses = ModelUtils::getAllAncestorsOf( $po_ins->getClass() ); // puede ser en cualquier orden!
      
      /*
      // Quiero la clase de nivel 1
      $level1Class = NULL;
      foreach ( $superclasses as $class )
      {
      	if ( get_parent_class( $class ) == PersistentObject )
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
      $superclasses[] = $po_ins->getClass();
      
      // Mapa de clases y subclases que se mapean en la misma tabla.
      $struct = self::getMultipleTableInheritance( $superclasses );
      
      // TODO: Partial Instances no considera valores, tengo que setear los valores a mano a partir de los valores de po_ins.
      $partialInstances = self::getPartialInstantes( $struct ); // Instancias de las clases en $superclasses que solo tienen los atributos que van en cada tabla. Cada clase de estas se mapea directamente con una tabla.
      
      // Seteo valores de campos de cada clase parcial
      // Nota: el identidicador de la instancia es el id en la tabla de mayor nivel, 
      //   los identificadores de los registros parciales se calculan a partir del 
      //   atributo "super_id" que forma una lista implicita de los registros 
      //   de una misma clase entre tablas que mapean herencia.
      /*
      $attrs_values = $po_ins->getAttributeValues();
      foreach ( $attrs_values as $attr => $value )
      {
         if (!PersistenObject::isInyectedAttribute( $attr ))
         {
         	// no se de que clase es atributo...
         }
      }
      */
//      Logger::getInstance()->log( __FILE__ . ".getPartialInstancesToSave [set partial instances attributes] =================" );
      
//      Logger::struct( $po_ins, "PO_INS" );
      
      foreach ( $partialInstances as $partialInstance )
      {
         $attrs_values = $partialInstance->getAttributeTypes(); // El tipo no lo uso para nada, solo necesito la declaracion de atributos.
         foreach ( $attrs_values as $attr => $type )
         {
            //echo $partialInstance->getClass() . " " . $po_ins->getClass() . " $attr<br />";
            
            if ( $partialInstance->getClass() === $po_ins->getClass() ) // Seteo atributos inyectados tambien, xq son de esta instancia!
            {
//               echo "po_ins->aGet($attr) = " . $po_ins->aGet($attr) . "<br/>";
               //Logger::getInstance()->log( "SET ATTR OF SAME CLASS: " . $attr . " VALOR: " . $po_ins->aGet($attr) );
            	$partialInstance->aSet($attr, $po_ins->aGet($attr));
            }
            else
            {
               if (!PersistentObject::isInyectedAttribute( $attr )) // TODO: luego veo como setear los atributos inyectados... El valor de "class" se inyecta solo! 
               {
                  //Logger::getInstance()->log( "SET NOT INYECTED ATTR: " . $attr . " VALOR: " . $po_ins->aGet($attr) );
         	      $partialInstance->aSet($attr, $po_ins->aGet($attr));
               }
               else
               {
                  // Si es inyectado pero es un super_id_X tengo que setearlo.
                  if ( YuppConventions::isRefName($attr) )
                  {
                  	//Logger::getInstance()->log( "SETEA INYECTED ATTR: " . $attr . " VALOR: " . $po_ins->aGet($attr) );
                     $partialInstance->aSet($attr, $po_ins->aGet($attr));
                  }
               	else
                  {
                     //Logger::getInstance()->log( "NO SETEA INYECTED ATTR: " . $attr . " VALOR: " . $po_ins->aGet($attr) );
                  }
               }
            }
         }
         
         // El deleted, si la instancia a salvar esta deleted, todos los registros deben estarlo!
         $partialInstance->setDeleted( $po_ins->getDeleted() );
         
      } // getPartialInstancesToSave
      
      // OBS: los id y super_id deberia irlos calculando y seteando el insert o update que es quien maneja eso.
      
      
//      echo "<pre>getPartialInstancesToSave partialInstances2 =========================================<br/>";
//      print_r( $partialInstances );
//      echo "</pre>";
      
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
      //return ($parentClass !== PersistentObject && $obj->getWithTable() !== $pins->getWithTable());
      
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
         
         if ($parentClass !== PersistentObject)
         {
            if ($obj->getWithTable() !== $pcIns->getWithTable()) $found = true;
         }
         else
         {
         	break; // Si llego a PO sin encontrar, tengo que parar el loop.
         }
         
         $objToIterate = $pcIns;
      }
      
      return $found;
   }
   
   
   /**
    * OJO, esta se usaria solo si es una instancia parcial, ya que si al objeto le hice 
    * "get", el get me tiene que garantizar que el objeto esta completo y es el ultimo 
    * de la herencia (viendo su atributo "class").
    * 
    * Para un objeto MTI, si no es la ultima instancia de la estructura de herencia 
    * (la que tiene todos los super_id_xx) carga la ultima y la devuelve, si no 
    * devuelve el mismo objeto.
    */
   public static function getLastMTIInstance( $obj )
   {
      // La condicion que chekeo para saber si no es la ultima instancia de 
      // la herencia es ver si su clase de instancia difiere de su atributo 
      // class, el cual es la clase que genera la tabla donde se guarda la 
      // ultima instancia.
      
      $classAttr = $obj->getClass(); // Atributo class de la instancia
      
      if ( $classAttr === get_class($obj) ) return $obj; // Si coincide, es instancia de la ultima clase.
      
      $condition = Condition::EQ( YuppConventions::tableName( $classAttr ), 
                                  YuppConventions::superclassRefName( get_class($obj) ),
                                  $obj->getId() );
                                  
      $params = array();
      eval('$list = $classAttr::findBy( $condition, &$params );'); // Devuelve un PO.
      
      /*
      // Quiero el PO de clase $persistentInstance->getClass() que tenga el id de 
      // $persistentInstance en su atributo super_id_get_class($persistentInstance).
      //
      $cins = new $classAttr();
      
      $tableName = YuppConventions::tableName( $cins ); // tableName es de la tabla con la ultima clase de la estructura de herencia!
      
      // Con el id del super_id del PO que me pasan y la tabla de la ultima clase de la estructura de herencia, 
      // pido el registor de esa ultima clase que es la que tiene todos los super_ids.
      //
      $condition = Condition::EQ( $tableName, YuppConventions::superclassRefName( $persistentClass ), $id );
      $params = array(); // Para pasarle la referencia a un array vacio.
      $list = $this->findByAttributeMatrix( $cins, $condition, $params ); // Devuelve matriz de atributos
      */
      
      // Verifico resultado, deberia ser un unico registro porque se pide con id de instancia.
      $size = count( $list );
      if ($size != 1 ) // resultado esperado es 1
      {
         throw new Exception("Se esperaba obtener exactamente un registro y hay $size");
      }

      return $list[0];
   }
   
}
?>