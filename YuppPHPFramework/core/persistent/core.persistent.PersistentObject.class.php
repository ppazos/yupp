<?php
/**
 * Este archivo contiene la definicion de la clase persistente, que tiene soporte para delaciones 
 * unidireccionales y bidireccionales 1-1, 1-n, n-n. Tambien soporta herencia.
 * 
 * Created on 15/12/2007
 * Modified on 30/05/2008
 * 
 * @name core.persistent.PersistentObject.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.1.0
 * @package core.persistent
 * 
 * @link ... (PHPDoc)
 */


// FIXME: sacar esto y ponerle LoadClass.
include_once "../core.Constraints.class.php";
include_once("../utils/core.utils.Callback.class.php");

/*

TODOS:

- restricciones que afectan la generacion del esquema.
  - nullable, not null.
  - si es str, ver que si es mas chicho que 255 sea varchar y mas grande otra cosa.
  - foreign keys.
  - unique (indice)

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
- Si la relacion es 1..* y el * no tiene belongsTo, IGUAL SE DEBERIA CONSIDERAR COMO QUE EL * tiene belongsTo el 1 !!!!!!!!!!!!!!!!!!!!! (afecta el PM.get_many_assocs)

*/


/**
 * Esta clase implementa toda la logica necesaria para modelar objetos persistentes.
 * @package core.persistent
 * @subpackage classes
 */
class PersistentObject {

   const NOT_LOADED_ASSOC = -1; // Codigo de asociacion no cargada, util para lazy loading.

   // Tipos de hasMany
   const HASMANY_COLECTION = "colection";
   const HASMANY_SET       = "set";
   const HASMANY_LIST      = "list";

   //private $_class = PersistentObject;
   // Necesario para poder llamar a las funciones CRUD de forma estatica.
   protected static $thisClass; // auxiliar para metodos estaticos...

   //protected $name;

   protected $withTable;
   protected $attributeValues = array(); // Mapa: Nombres de los atributos -> Valor
                                         // Para elementos que estan declarados en hasMany, lo que hay es una lista de objetos.

   protected $attributeTypes = array();  // En cada indice tiene el tipo del atributo simple.
   protected $hasOne = array();          // Asociaciones simples con otras clases persistentes.
   protected $hasMany = array();         // Lista de para declarar los tipos de los objetos que se tienen many.
   protected $hasManyType = array();     // Para cada atributo hasMany, tiene su tipo, que puede ser:
                                         // - COLECTION (comportamiento comun)
                                         // - SET       (igual a colection pero sin repetidos)
                                         // - LIST      (igual a colection pero establece un orden entre los elementos)

   protected $belongsTo = array();       // Para marcar relaciones de pertenencia con otros objetos relacionados. Ya sean relaciones 1..1, 1..*, *..*
                                         // TODO: Posiblemente para modelos complejos, el belongsTo tenga que ser a nivel de rol de asociacion no a nivel de clase.

   // Validacion.
   protected $constraints = array();     // Array de Array de constraints, dado que varias constraints se pueden aplicar al mismo campo.
   protected $errors = array();          // Mensajes de validacion, misma estructura que constraints.


   protected $multipleTableIds = array(); // Array asociativo por Nombre-de-superclas, y valor el id en la tabla correspondiente.
                                          // Este atributo sirve para realizar las operaciones del save (update) sin tener que 
                                          // consultar los ids de las instancias parciales en sus respecivas tablas, asi ya 
                                          // cuando carga la instancia se cargan los ids de las instancias parciales (en cada tabla) 
                                          // en este array.

   public function getMultipleTableIds()
   {
   	return $this->multipleTableIds;
   }
   
   public function getMultipleTableId( $superClass )
   {
      // FIXME: chek key
   	return $this->multipleTableIds[$superClass];
   }
   
   public function addMultipleTableId( $superClass, $id )
   {
   	$this->multipleTableIds[$superClass] = $id;
   }
   
   /**
    * Setea los atributos super_id_SuperClase desde la estructura de MTIds.
    */
   public function updateSuperIds()
   {
      //Logger::struct( $this->multipleTableIds, "PO.updateSuperIds (". $this->getClass() .")" );
      
      foreach ( $this->multipleTableIds as $class => $id )
   	{
         $attr = YuppConventions::superclassRefName( $class ); // super_id_SuperClase
         $this->aSet($attr, $id);
      }
   }
   
   /**
    * Actualiza la estructura de MTIds con los valores de los super_id_XXX.
    */
   public function updateMultipleTableIds()
   {
      foreach ( $this->attributeValues as $attr => $value )
      {
      	if ( YuppConventions::isRefName($attr) )
         {
         	$class = YuppConventions::superclassFromRefName( $attr );
            $this->multipleTableIds[$class] = $value;
         }
      }
   }
   
   public function getMTIAttributes()
   {
      $res = array();
      foreach ( $this->attributeValues as $attr => $value ) // No interesa el valor.
      {
         if ( YuppConventions::isRefName($attr) )
         {
            $res[] = $attr; // ref name
         }
      }
      return $res;
   }
   

   /**
    * Agrega una lista de restricciones a un atributo.
    */
   public function addConstraints( $attr, $constraints )
   {
      // TODO: chek, el atributo existe.
      // TODO: chek, constraints debe ser 1) un array de 2) restricciones validas.
      // Si ya hay constraints, no las redefine.
      // Deberia chekear por tipo de cosntraint tmb? asi puedo definir constraints para 
      // distintos atributos en distintas clases en la jerarquia de herencia?
   	if ( !array_key_exists($attr, $this->constraints) )
      {
      	$this->constraints[$attr] = $constraints;
      }
   }

   // ==================================================
   // Seteo de atributos ===============================
   public function addAttribute( $name, $type )
   {
   	$this->attributeTypes[$name] = $type;
   }
   
   /**
    * Se utiliza en la generacion del esquema para soportar herencia en multiples tablas.
    */
   private function removeAttribute( $attr )
   {
   	if ( array_key_exists($attr, $this->attributeTypes) )
      {
         unset( $this->attributeTypes[$attr] ); // forma de remover un valor de un array...
         //$this->attributeTypes[$attr] = NULL;
         //$this->attributeTypes = array_filter( $this->attributeTypes ); // saco nulls (que forma fea de PHP de remover elementos de un array)
      }
   }
   public function addHasOne( $name, $clazz )
   {
      $this->hasOne[$name] = $clazz;
   }
   
   /**
    * Agrega un atributo hasMany a la instancia de la clase persistente.
    * 
    * @param String name nombre del atributo hasmany
    * @param String class clase de los elementos contenidos en la coleccion de elementos
    * @param String type tipo del atributo hasMany, dice si se comporta como una coleccion, un conjunto o una lista
    */
   public function addHasMany( $name, $clazz, $type = self::HASMANY_COLECTION )
   {
      $this->hasMany[$name]     = $clazz;
      $this->hasManyType[$name] = $type;
   }
   
   
   public function getHasManyType( $attr )
   {
   	if ($this->hasMany[$attr] === NULL) throw new Exception("La clase no tiene un atributo hasMany con nombre '$attr' " . __FILE__ . " " . __LINE__);

      return $this->hasManyType[$attr];
   }
   
   // /Seteo de atributos ==============================
   // ==================================================

   // ====================================================
   // Para saber cuando se salvo el objeto.
   protected $sessId = NULL;
   public function setSessId( $sessId )
   {
      $this->sessId = $sessId;
   }
   public function getSessId()
   {
      return $this->sessId;
   }
   public function isSaved( $sessId )
   {
      return ($this->sessId == $sessId);
   }
   // ====================================================
   // Para saber si se empezo un recorrido para salvar el objeto y detectar posibles loops en la recorida para salvar el modelo.
   protected $loopDetectorSessId = NULL;
   public function setLoopDetectorSessId( $sessId )
   {
      $this->loopDetectorSessId = $sessId;
   }
   public function isLoopMarked( $sessId )
   {
      return ($this->loopDetectorSessId == $sessId);
   }
   // ====================================================

   // Registro de callbacks
   protected $afterSave = array();
   protected $beforeSave = array();

   public function registerAfterSaveCallback( Callback $cb )
   {
   	  $this->afterSave[] = $cb;
   }
   public function registerBeforeSaveCallback( Callback $cb )
   {
      $this->beforeSave[] = $cb;
   }

   // Operaciones de ejecucion de callbacks registrados
   private function executeAfterSave()
   {
      Logger::getInstance()->po_log("Excecute after save ". get_class($this));

      foreach ( $this->afterSave as $cb )
      {
      	 $cb->execute();
         Logger::getInstance()->po_log($cb->__toString());
      }

      // Una vez que termino de ejecutar, reseteo los cb's registrados.
      $this->afterSave = array();
   }
   private function executeBeforeSave()
   {
      Logger::getInstance()->po_log("Excecute before save ". get_class($this));

      foreach ( $this->beforeSave as $cb )
      {
         $cb->execute();
         Logger::getInstance()->po_log($cb->__toString());
      }

      // Una vez que termino de ejecutar, reseteo los cb's registrados.
      $this->beforeSave = array();
   }
   // ====================================================


   // Llamada por las subclases, sirve para hacer proceso de los atributos. (estos ya deben estar seteados!)
   /**
    * @param $args mapa de nombres de atributos y valores, si es pasado el objeto inicializa
    *              sus campos con esos valores.  
    * @param $isSimpleInstance indica si se hace el proceso interno necesario para utilizar 
    *              la clase o no, en caso de no hacerse, la clase no puede ser utilizada por 
    *              el usuario. Esta opcion es necesaria para proceso interno. Por ahora se 
    *              utiliza solo cuando hay que crear una instancia para averiguar el nombre de la tabla.
    */
   public function __construct( $args = array(), $isSimpleInstance = false )
   {
      //Logger::getInstance()->log("PersistenObject::construct");


      // Este atributo lo inyecto aunque la instancia sea simple, porque se utiliza en el YuppConventions::tableName.
      // 5: Nombre de la clase, para soporte de herencia.
      $this->attributeTypes[ "class" ]  = Datatypes::TEXT; // Los tipos de los deleted son boolean.
      $this->attributeValues[ "class" ] = get_class($this); // No esta borrado.


      // Si es simple, no hago nada.
      if ( $isSimpleInstance ) return;
       

      // Inyecta atributos de referencia hasOne.
      // VERIFY: Ojo, yo puedo tener un hasOne, pero el otro puede tener un hasMany con migo adentro! o sea *..1 bidireccional!!!!! no 1..1
      // 1: Se fija todas las relaciones 1??..1 a otras clases persistentes y agrega lso atributos de FK como "email_id".
      //    Si se hace getEmailId, se devuelve el valor del id ese atributo, si es que no es null. TODO: Modificar __call.
      if ( $this->hasOne )
      {
         foreach ( $this->hasOne as $attr => $type )
         {
            if ( is_subclass_of($type, PersistentObject) ) // FIXME: Perooo, todos los hasOne son subclases de PO...
            {
               $newAttrName = DatabaseNormalization::simpleAssoc( $attr ); // Atributo email_id inyectado!

               $this->attributeTypes[ $newAttrName ]  = Datatypes::INT_NUMBER;  // Los tipos de los ids son int.
               $this->attributeValues[ $newAttrName ] = NULL; // FIXME: Esto no es un objeto, es su Id, por eso le pongo NULL y no NOT_LOADED.

               // Inyecto el atributo "email" y lo inicializo en NOT_LOADED...
               $this->attributeValues[ $attr ] = self::NOT_LOADED_ASSOC;

               // ningun objeto asociado, pero en este caso es que el objeto no esta ni siquiera cargado, para poner NULL habria que ver si
               // hay algun objeto y constatar de que no hay ninguno..
            }
            else
            {
            	throw new Exception("HasOne, atributo $attr del tipo $type no es persistente.");
            }
         }
      }

      // 2: Inicializo los arrays para los valores de los objetos de los que se tienen varios.
      // FIXME: si en args viene un array con POs para un atributos hasMany, tengo que setearlo... y verificar q es un array y verificar que lo que tiene el array son objetos del tipo declarado en el hasMany.
      if ( $this->hasMany )
      {
         foreach ( $this->hasMany as $attr => $type )
         {
            if ( is_subclass_of($type, PersistentObject) ) // FIXME: Perooo, todos los hasMany son subclases de PO...
            {
               //$this->attributeValues[ $attr ] = array(); // Si hay un array no se si es que el array se cargo y es vacio o si es que todavia no se cargo
               $this->attributeValues[ $attr ] = self::NOT_LOADED_ASSOC; // TODO: OJO! puede haber operaciones ahora que piensan que esto es un array y no consideran en NOT LOADED...
            }
            // Else: podria ver como manejar listas de objetos simples como enteros y strings.
            // OJO, las listas de atributos simples no se si estaria bien declararlas en hasMany!
         }
      }

      // debe ir aqui porque si me viene un valor para un atributo inyectado y hago esto luego, 
      // no me va a poner el valor xq el atributo no estaba inyectado!

      // 3: Inyecta el atributo id de tipo int.
      $this->attributeTypes[ "id" ]  = Datatypes::INT_NUMBER; // Los tipos de los ids son int.
      $this->attributeValues[ "id" ] = NULL; // No tengo ningun objeto asociado.

      // 4: Inyecta el atributo deleted de tipo boolean.
      $this->attributeTypes[ "deleted" ]  = Datatypes::BOOLEAN; // Los tipos de los deleted son boolean.
      $this->attributeValues[ "deleted" ] = false; // No esta borrado.



       
      // super_id_XXX
      $superclasses = ModelUtils::getAllAncestorsOf( $this->attributeValues[ "class" ] );
       
      //Logger::struct( $superclasses );
       
      //Logger::getInstance()->log( "--------------------- table this( ". $this->getClass() ." )  " . YuppConventions::tableName( $this ) . " ----------------------" );
       
      /*
       * FIXME: Debe hacerse como se inyectan los super_ids en PM->generateAll (1844)
       * Para: A <- A1 <- C <- C1 <- G <- G1, en G genera super_id_A, super_id_A1, super_id_C, super_id_C1.
       * No deberia generar super_id_A1 y super_id_C1 porque son los mismos ids que super_id_A y super_id_C 
       * respectivamente, porque A y A1, C y C1 se mapean cada par a la misma tabla.
       */
      
      
      /*
      echo $this->attributeValues[ "class" ] . "<hr/>";
       
      Logger::struct( $superclasses, "SUPERCLASES" );
       
      $cnt = count( $superclasses );
      echo "<h1>COUNT $cnt 1</h1>";
       
      // OBS: esta es recursiva xq hace new de clases de dominio...
      // Para hacerlo distinto deberia pedir las superclases de esta que generan tablas y listo.
      // o sea> MultipleTableInheritanceSupport::superclassesThatGenerateTables()
      // que es lo mismo que getMultipleTableInheritanceStructureToGenerateModel pero solo devuelve las keys de eso.
       
      foreach ( $superclasses as $sclass )
      {
         //Logger::getInstance()->log( "table this: " . YuppConventions::tableName( $this ) );
         //Logger::getInstance()->log( "table $sclass: " . YuppConventions::tableName( new $sclass() ) );
         
         Logger::getInstance()->log( "<h1>$sclass ". __LINE__." 1</h1>");
         
         echo "<h1>COUNT $cnt 2</h1>";
         
         //if ( !PersistentManager::isMappedOnSameTable( get_class($this), $sclass ) ) // No puedo hacer esto porque quiere instanciar esta clase y entra en llamadas recursivas infinitas...
         if ( YuppConventions::tableName( $this ) !== YuppConventions::tableName( new $sclass() ) )
         {
            echo "<h1>COUNT $cnt 3</h1>";
            
             Logger::getInstance()->log( "<h1>$sclass ". __LINE__. " 2</h1>"); // RARO: si superclasses es vacio imprime esta linea pero no hace los logs anteriores!
            Logger::getInstance()->log( "DISTINTO: " . YuppConventions::tableName( $this ) . " " . YuppConventions::tableName( new $sclass() ) );
            
            $attr_scid = YuppConventions::superclassRefName( $sclass );
            
            Logger::getInstance()->log( "INYECTA: " . $attr_scid );
            
          	$this->attributeTypes[$attr_scid ]  = Datatypes::INT_NUMBER; // Los tipos de los ids son int.
            $this->attributeValues[ $attr_scid ] = NULL;
         }
      }
      */
     
      $superclassesWithTable = MultipleTableInheritanceSupport::superclassesThatGenerateTables( $this->attributeValues[ "class" ] );
      foreach ( $superclassesWithTable as $scwt )
      {
         $attr_scid = YuppConventions::superclassRefName( $scwt );
      	$this->attributeTypes [ $attr_scid ] = Datatypes::INT_NUMBER; // Los tipos de los ids son int.
         $this->attributeValues[ $attr_scid ] = NULL;
      }
     
      /*
         while ( $parent_class !== PersistentObject )
         {
            if ( array_key_exists($parent_class, $struct) ) // Si es una clase principal
            {
               // FIXME: el nombre del atributo deberia se parte de las convenciones o de DB NORMALIZATION!
               $c_ins->addAttribute( YuppConventions::superclassRefName($parent_class), // super_id_nomclase
                                     Datatypes::INT_NUMBER );
                                     
               Logger::getInstance()->log( "Inyecta super_id a: " . $parent_class );
            }
            
            $parent_class = get_parent_class($parent_class); // Le puedo pasar instancias o nombres de clases!
         }
         // /Inyecto FKs
      */
       

      // Me fijo si en args viene algun valor de atributo
      // FIXME: no deberia poder modificar valor de atributos inyectados, el comportamiento posterior es impredecible.
      foreach ( $args as $attr => $value )
      {
          // OJO, PUEDO INICIALIZAR ATRIBUTOS EN HASONE Y HASMANY TAMBIEN, EL PREGUNTAR PUEDE SERVIR PARA HACER CHECKEOS SOBRE EL VALOR QUE SE PASA, si en un PO para hasOne, si es un array para hasMany.
          if ( array_key_exists($attr, $this->attributeTypes) ||
               array_key_exists($attr, $this->hasOne) ||
               array_key_exists($attr, $this->hasMany) )
          {
             // TODO: verificar que es del mismo tipo, verificar que es un valor valido con respecto a las constraints.
             // esto es un setAttrbuteValue( $attr ) pero mas rapido...
             $this->attributeValues[$attr] = $value;
          }
      }



//      echo "<h1><pre>PO CONSTRUCT<br/>";
//      print_r( $this->constraints );
//      echo "</pre></h1>";
   }


   // Consulta sobre el tipo de atributo: inyectado/no inyectado
   public static function isInyectedAttribute( $attr )
   {
   	if ( strcmp($attr, "id")       == 0 ) return true;
      if ( strcmp($attr, "deleted")  == 0 ) return true;
      if ( strcmp($attr, "class")    == 0 ) return true;
      if ( String::startsWith($attr, "super_id_") ) return true; // No va mas este atrib
      return false;
   }




   // La idea es llamarla desde getSimpleAssocValues para obtener todos los objetos persistentes relacionados 1..1
   public function isSimplePersistentObject( $attr )
   {
      $type = $this->hasOne[ $attr ];
      if (!$type) return false; // Ni siquiera le pase un attributo valido...

      // TODO (T#6): Esto es una verificacion de correctitud del modelo creado... no se si va aca... deberia ser algo previo, por ejemplo hacerse cuando se instala un componente.
      if ( is_subclass_of($type, PersistentObject) )
      {
         // Chek 1: Fijarsse si es una lsita persistente (todavia no hecho), return false.
         // else
         return true;
      }
      return false; // Si lelga aca es cualqeuir cosa....
   }


   /**
   Setea los valores de los "atributos simples" de la clase cuyos nombres aparecen en el mapping.

   Los valores que no sean de campos de la clase son ignorados.

   @param Map params  mapping nombre campo / valor.
   */
   public function setProperties( $params )
   {
      foreach ( $this->attributeTypes as $attr => $type )
      {
         // 1: fijarse si esta en params
         // 2: verificar si el valor que tiene en params es del mismo tipo que el atributo
         //    - Si el tipo es numerico, y el valor es string, ver que sea un string numerico.  http://www.php.net/is_numeric (ojo con numeros negativos o si empiezan con "." o ",", probar!!!)
         //    - Si el tipo es date o time, y el tipo es un string, ver que el string tiene forma de date o time.
         //    - Distinguir entre valor cero y valor null.

         // TODO: Ver como se podrian tambien setear listas de "objetos simples" (no es la idea que esto setee atributos que son PO, solo atributos simples)
         if ( array_key_exists($attr, $params) && !$this->isInyectedAttribute( $attr )) // IMPORTANTE: id, class, deleted no se pueden setear por set properties!!!
         {
            // Esto es set$attr pero mas rapido!
            // TODO: Chekeos de tipos...
            // WARNING: Esto solo setea atributos simples! Hay que ver si puedo hacer el tema de setear atributos de clases asociadas... (depende de la notacion de las keys de params)
            // SI HAGO TODO EL CHEKEO EN setAttributeValue, solo llamo a esa y listo...
            $this->attributeValues[ $attr ] = $params[$attr];
         }
      }
   }

   /**
    * Obtiene nombre de la tabla a la cual se mapea el objeto.
    */
   public function getWithTable()
   {
      return $this->withTable;
   }

   /**
    * Establece el nombre de la tabla a la cual se mapea el objeto. Necesario para generar el esquema.
    */
   public function setWithTable( $tableName )
   {
   	$this->withTable = $tableName;
   }


/* Ahora se usa aGet
   // TODO: Al setear un objeto de hasOne deberia setear el "email_id" (que podria ser null o un entero)...
   // Devuelve valores de atributos, mas estructurado que OO, pero es para uso interno desde DAL por ejemplo.
   public function getAttributeValue( $attr ) // Cambie el nombre de get xq se choca con el get que quiero poner de wrapper del PM.
   {
      // Si es un atributo de referencia de aosciacion hasOne (como email_id), me fijo en el id del elemento! me fijo si tengo "email" en hasOne.
      // $refAttrName = DatabaseNormalization::simpleAssoc( $attr );
//      Logger::getInstance()->error( "Pido attr getAttributeValue(): " . $attr);

//      echo "<h1>getAttributeValue getAssocRoleName: $attr</h1>";
//      $attr = self::getAssocRoleName( $attr ); // Podria tener codificador el nombre de la asociacion.
//      echo "<h1>getAttributeValue getAssocRoleName: $attr</h1>";

      // CHECK 1: El atributo esta en la lista de atributos?
      if ( array_key_exists($attr, $this->attributeTypes) )
      {
         // AHORA SE SETEA EL email_id al setear el email, asi que el valor se busca derecho en email_id.
         return $this->attributeValues[ $attr ];
      }
      else if ( array_key_exists($attr, $this->hasOne) )
      {
         return $this->attributeValues[ $attr ]; // el valor esta tambien en "attributeValues"
      }
      else if ( array_key_exists($attr, $this->hasMany) )
      {
      	 // ====================================================================================
      	 // TICKET #11
      	 // TODO: Si agrego soporte para lazy loading, aca deberia verificar si
      	 //       tengo o no cargados los objetos y cargarlos si no los tengo.
         return $this->attributeValues[ $attr ]; // el valor esta tambien en "attributeValues"
      }
      else
      {
         throw new Exception("El atributo ". $attr ." no existe en la clase (" . get_class($this) . ")");
      }

   } // getAttributeValue
*/

   /* ahora se usa aSet
   public function setAttributeValue( $attr, $value )
   {
//      $attr = self::getAssocRoleName( $attr ); // Podria tener codificador el nombre de la asociacion.
//      echo "<h1>setAttributeValue getAssocRoleName: $attr</h1>";

	   // VERIFY: CUal es la joda de discutir en que lista esta si al final hago lo mismo ??? SIRVE PARA VERIFICAR QUE LO QUE ESTOY SETEANDO ES VALIDO.
      // CHECK 1: El atributo esta en la lista de atributos?
      if ( array_key_exists($attr, $this->attributeTypes) )
      {
      	// TODO: Verificar que el valor es de tipo simple.
         $this->attributeValues[ $attr ] = $value;
      }
      else if ( array_key_exists($attr, $this->hasOne) )
      {
         $_obj = $value;
         if ( is_subclass_of( $_obj, PersistentObject ) )
         {
            // seteo "email"
            // El valor tambien va en attrValues!!!
            $this->attributeValues[ $attr ] = $_obj;

            // FIXME: Si seteo NULL no puedo preguntarle el id!!!
            // seteo "email_id"
            $refAttrName = DatabaseNormalization::simpleAssoc( $attr );
            $this->attributeValues[ $refAttrName ] = $_obj->getId(); // Seteo tambien "email_id", puede ser NULL !!!
         }
         else
         {
            // El objeto no es persistente!!!!!!
         }
      }
      else if ( array_key_exists($attr, $this->hasMany) ) // El valor deberia ser una lista de objetos....
      {
         $_objList = $value; // TODO: Deberia ser un array, ademas deberia ser de objetos persistentes.

         if (!is_array($_objList)) throw new Exception("El valor para el atributo ". $attr ." debe ser un array.");

         // El valor tambien va en attrValues!!!
         $this->attributeValues[ $attr ] = $_objList;
      }
      else
      {
         throw new Exception("El atributo ". $attr ." no existe en la clase (" . get_class($this) . ")");
      }

   } // setAttributeValue
   */

   /**
    * Devuelve los valores de todos los atributos.
    */
   public function getAttributeValues()
   {
      return $this->attributeValues;
   }

   // Solo de atributos simples :)
   public function getAttributeTypes()
   {
      return $this->attributeTypes;
   }

   // Busca en hasOne y hasMany tambien...
   // La uso en el PM.get para cargar objetos asociados.
   public function getType( $attr )
   {
       if ( array_key_exists($attr, $this->hasOne) )
       {
          return $this->hasOne[ $attr ];
       }
       else if ( array_key_exists($attr, $this->hasMany) )
       {
          return $this->hasMany[ $attr ];
       }
       else if ( array_key_exists($attr, $this->attributeTypes) )
       {
          return $this->attributeTypes[ $attr ];
       }
       return NULL; // except?? el attr no existe.
   }

   /**
    * Obtiene errores de validacion para los atributos que teniendo restricciones, se verificaron y fallaron.
    */
   public function getErrors()
   {
      return $this->errors;
   }
   
   public function getFieldErrors( $attr )
   {
   	if (array_key_exists($attr, $this->errors))
      {
      	return $this->errors[$attr];
      }
      return NULL;
   }
   
   public function hasErrors()
   {
   	return ($this->errors !== NULL) && (count($this->errors) == 0);
   }

   // Utilizada por PersistentManager para crear tablas intermedias para las asociaciones *..*
   public function getHasMany()
   {
      return $this->hasMany;
   }

   public function getHasOne()
   {
      return $this->hasOne;
   }

   // VALORES DE ASOCIACIONES ====================================================================

   // Se usa en PM.get para saber cuales so los atributos de referencia hasOne
   public function getSimpleAssocAttrNames()
   {
      $res = array();
      foreach ( $this->hasOne as $attr => $type )
      {
         $assocAttrName = DatabaseNormalization::simpleAssoc( $attr );
         $res[] = $assocAttrName;
      }
      return $res;
   }

   // Devuelve solo valores de atributos simples.
   public function getSimpleAttrValues()
   {
      $res = array();
      foreach ( $this->attributeTypes as $attr => $type )
      {
         $res[$attr] = $this->attributeValues[$attr];
      }
      return $res;
   }

   // Retorna los objetos persistentes simples asociados (1..1)
   public function getSimpleAssocValues()
   {
      $res = array();
      foreach ( $this->hasOne as $attr => $type )
      {
         $value = $this->aGet( $attr );
         if ( $value !== NULL )  // FIXME: xq no retorno los valores null? null es un valor tambien...
         {                      // EL TEMA ES QUE SI RETORNO NULL EL TIPO VA A QUERER GUARDAR NULL... O SEA SI ES NULL NO GUARDO NADA.
                                // TAMBIEN PASA QUE SI SE PONE EN NULL UN ATRIBUTO, DEBERIA PONERSE EN NULL DICHO ATRIBUTO EN LA BASE.. (ESTO ES UN FIXME!)
             $res[$attr] = $value;
         }
      }
      return $res;
   }


   // Se usa en PM.get para saber cuales so los atributos de referencia hasMany
   public function getManyAssocAttrNames()
   {
      $res = array();
      foreach ( $this->hasMany as $attr => $type )
      {
         $res[] = $attr;
      }
      return $res;
   }

   // Retorna los objetos persistentes con hasMany asociados (1..*)
   public function getManyAssocValues()
   {
      $res = array();
      foreach ( $this->hasMany as $attr => $type )
      {
         $objectList = $this->attributeValues[ $attr ];

         if ( $objectList == self::NOT_LOADED_ASSOC ) $res[$attr] = array();
         else
         {
            // Saco nulls del array
            $res[$attr] = array_filter( $objectList ); // La forma PHP de hacerlo... array sin NULLs...
         }
      }
      return $res;
   }
   // /VALORES DE ASOCIACIONES ====================================================================



   // Funcion que verifica si el objeto esta correctamente formado.
   public function verify()
   {
      // Check 1: Tener los mismos atributos en Values que en Types.
      // Esto se puede hacer mejor, o sea, que todos los atributos esten en types, y luego procesando se agregar a values con valores null.
      // Y si se define algo en values, es porque son los valores por defecto.

      // Check 2: Que los atributos definidos en constraints esten en la definicion de tipos.

      // Check 3: Verificar que el tipo de las variables es compatible con las restricciones que se le quieren aplicar.

      // Check 4: No puede tener restricciones incompatibles para el mismo atributo (p.e.: nullable con notNull)

      // Chek 5: Verifica tipos de los atributos seteados por defecto, como numeros con strings seteados (q no tengan forma de entero), lo mismo con fechas y tiempos.

      // Chek 6: Verifica que los valores por defecto cumplan las restricciones.

   }

   public function getConstraints( $attr = NULL )
   {
      if ( $attr === NULL ) return $this->constraints;
      
      if ( $this->constraints[ $attr ] !== NULL )
      {
         return $this->constraints[ $attr ];
      }

      return array(); // No tiene restricciones
   }
   
   /**
    * Devuelve la restriccion para el atributo que sea del tipo dado, si no la encuentra retorna NULL.
    */
   public function getConstraintOfClass( $attr, $class )
   {
      foreach ( $this->getConstraints($attr) as $constraint )
      {
      	if ( get_class($constraint) === $class )
         {
         	return $constraint;
         }
      }
      return NULL;
   }

   // ===================================================================
   // Verificacion de restriccioens que afectan la generacion del esquema
   public function nullable( $attr )
   {
      // Atributos inyectados no son nullables.
      if (self::isInyectedAttribute( $attr )) return false;
      
      // TODO: Si el atributo es una FK generada para relaciones hasOne o hasMany,
      // deberia verificar si la relacion puede ser nullable o no para saber si ese atributo puede ser null.
      // Por ejemplo el atributo "email" es nullable, aqui pregunto si el atrivuto "email_id" es nullable,
      // me tengo que fijar si hay un atributo "email" (me doy cuenta del nombre porque le saco el "_id"),
      // luego me fijo si ese atributo tiene una contraint nullable, y hago el resto como siempre...
      //
      // OJO! es "email_id" cuando es un hasOne, todavia no se como se va a llamar la FK a
      // hasMany o en que direccion van a ir las relaciones.

      //Logger::getInstance()->log("Nullable ATTR1? $attr");

      // Si el que me pasan es un atributo de referencia hasOne (inyectado) si es nullable o 
      // no depende de si el atributo hasOne correspondiente es nullable o no.
      // TODO:? Si es un atributo de referencia hasOne, hacerlo siempre nullable 
      // podria hacer mas faciles las cosas para PM.generateAll y PM.generate.
      // Y al resto del funcionamiento no le afectaria en nada.
      if ( DatabaseNormalization::isSimpleAssocName( $attr ) ) // Si es un atributo autogenerado de aosciacion hasOne con otra clase, no va a haber contraint para ella, pero si para el atributo en hasOne que se llama dinstinto...
      {
         $attr = DatabaseNormalization::getSimpleAssocName( $attr );
      }
      

      //Logger::getInstance()->log("Nullable ATTR2? $attr");

      if ( $this->constraints[ $attr ] )
      {
         foreach ( $this->constraints[ $attr ] as $constraint )
         {
            if ( get_class($constraint) == Nullable )
            {
               return $constraint->getValue();
            }
         }
      }
      //return false; // Por defecto no es nullable.
      return true; // Por defecto es nullable. Es mas facil para generar las tablas, ahora se pone en not null solo si hay una restriccion que lo diga.
   }
   // ===================================================================


   // Valida el objeto.
   public function validate()
   {
      //Logger::getInstance()->log("PersistenObject::validate");
      // TODO: Verificar restricciones en objetos asociados.
      // FIX: Idea de pasarle un parametro 'cascade' booleano que si es true se fija
      //      si los objetos asociados son validos, si no solo chekea con el objeto actual.

      // TODO: Verificar restricciones sobre asociaciones (p.ej. NotNull)  (*****)

      $valid = true;
      $this->errors = NULL; // Reset

      if ($this->constraints)
      {
        // Para cada campo
        foreach ( $this->constraints as $attr => $constraintArray )
        {
           foreach ( $constraintArray as $constraint )
           {
              //if ( !$constraint->evaluate( $this->attributeValues[$attr] ) ) // NO PIDE HASONE...
              if ( !$constraint->evaluate( $this->aGet($attr) ) )
              {
                 $valid = false;

                 // TODO: Validar asociaciones hasOne !!!  (*****)

                 // Agrego el error a "errors"

                 // Si no esta inicializada // NO ES NECESARIO, AHORA LO INICIALIZO CON UN ARRAY.
                 //if (!$this->errors) $this->errors = array();

                 // Si no hay un vector de mensajes para este campo
                 if (!$this->errors[ $attr ]) $this->errors[$attr] = array();

                 // Agrego mensaje
                 // TODO: ver de donde sacar el mensaje segun el tipo de constraint.
                 // FIX: se pueden tener keys i18n estandar para problemas con constraints, y para resolver
                 //      el mensaje como parametros le paso la constraint, el atributo y el valor que fallo.
                 $err = "Error " . get_class($constraint) . " " . $constraint . " en " . $attr . " con valor ";

                 /*
                 // FIXME!!: BUG: Si el atributo es un string vacio, me muestra 0.

                 // Le pongo el valor que viola, pero si es 0 o null se confunde... por eso distingo usando is_null.
                 if ( !is_null( $this->attributeValues[$attr] ) ) $err .= (($this->attributeValues[$attr]) ? $this->attributeValues[$attr] : "0");
                 else $err .= (($this->attributeValues[$attr]) ? $this->attributeValues[$attr] : "NULL"); // OJO, esto puede ser null o cero!
                 */

                 if ( is_null( $this->attributeValues[$attr] ) ) $err .= (($this->attributeValues[$attr]) ? $this->attributeValues[$attr] : "NULL"); // OJO, esto puede ser null o cero!
                 else if ( is_string($this->attributeValues[$attr]) && strcmp($this->attributeValues[$attr], "")==0 ) $err .= "EMPTY STRING";
                 else $err .= (($this->attributeValues[$attr]) ? $this->attributeValues[$attr] : "0");

                 $this->errors[$attr][] = $err;
              }
           }
        }
      }

      return $valid;
   }

   /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   // Cuando hago getXXX o setXXX pasa por aca y se implementa aca, aunque los metodos no existan.
   //
   public function __call( $method, $args )
   {
      // OJO, en verdad si tiene el metodo, ya lo llama y no pasa por __call... esto esta de mas.
      // Si tiene algun metodo transient, lo llama. Puede ser un metodo definido en alguna de las clases que extienden esta.
      if (method_exists($this, $method))
      {
         return $this->{$method}( $args );
         // throw new Exception("unknown method [$method]");
      }

      //echo "<h1>". $method . " - " . substr($method,-8) ."</h1>";

      // getAttributeName()
      if ( substr($method,0,3) == "get" )
      {
         $attr = substr($method, 3); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...

         // Primera letra a minuscula
         $attr = strtolower(substr($attr, 0, 1)) . substr($attr, 1, strlen($attr)); // FIXME: Poner operacion en clase YuppString.

         return $this->aGet( $attr );
      }
      else if ( substr($method,0,3) == "set" ) // setAttributeName( value )
      {
         $attr = substr($method, 3); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...

         // =============================================================================================================
         // ESTRATEGIAS DE SET:
         // * Inmediato: se actualiza tambien la base. Mas simple, pero se tiene una consulta con cada set.
         // * Post set: se actualiza solo en memoria, la base se actualiza al hacer el save. Mas complejo xq se deben
         //             verificar cosas que cambiaron para eliminar objetos (asociaciones) de la base, se ahorra
         //             consultas al hacer set, pero se hacen mas consultas al hacer save.
         //
         // Sobre todo hay que tener cuidado si se hace un ser de un atributo hasMany, porque si le meto una lista
         // de objetos con set a un atributo hasMany tengo que eliminar las referencias anteriores en la base para
         // que no haya inconsistencias. Por lo que Set Inmediato seria una buena opcion.
         // =============================================================================================================

         // Primera letra a minuscula
         $attr = strtolower(substr($attr, 0, 1)) . substr($attr, 1, strlen($attr));
         $this->aSet( $attr, $args[0] );
      }
      else if ( substr($method,0,5) == "addTo" )
      {
         $attr = substr($method, 5); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...
         $attr = strtolower(substr($attr, 0, 1)) . substr($attr, 1, strlen($attr)); // Primera letra a minuscula
         $this->aAddTo($attr, $args[0]);
      }
      else if ( substr($method,0,10) == "removeFrom" )
      {
         $attr = substr($method, 10); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...

         // TODO:
         // Esto se ve afectado por el lazy load?
         // Si porque la busqueda actualmente se hace en memoria
         // Para hacerlo bien robusto, deberia cargar todo, buscar, eliminar en memoria y eliminar en la base (eliminar a relacion no el objeto!)
         //

         $attr = strtolower(substr($attr, 0, 1)) . substr($attr, 1, strlen($attr)); // Primera letra a minuscula

//echo "<h1>REMOVE FROM: $attr</h1>";

         $this->aRemoveFrom( $attr, $args[0] );

         /*
         $attr = self::getAssocRoleName( $attr ); // Podria tener codificador el nombre de la asociacion.

         // CHECK 1: El atributo esta en la lista de atributos hasMany
         if ( array_key_exists($attr, $this->hasMany) )
         {
            // Busco y si encuentro elimino.
            foreach ( $this->attributeValues[$attr] as $key => $value )
            {
               // WARNING: Es complicado xq no se si buscar por igualdad de elementos (===) o por comparacion del id.
               // por ahora busco por igualdad de elementos.
               if ( $value === $args[0] ) // FIXME: OJO COMPARACION DE OBJETOS... DEBERIA COMPARAR ids?
               {
                  $this->attributeValues[$attr][$key] = null;
                  return;
               }
            }
         }
         else
         {
            throw new Exception("El atributo ". $attr ." no existe en la clase (" . get_class($this) . ")");
         }
         */
      }
      else if ( substr($method,-8) == "Contains" )
      {
          $hasManyAttr = substr($method,0,strlen($method)-8);
          $attr_w_assoc_name = $this->getFullAttributename( $hasManyAttr ); // Podria tener codificador el nombre de la asociacion.
          return $this->aContains( $attr_w_assoc_name, $args[0] );
      }
      else
      {
      	 throw new Exception("PO.__call: unknown method ". get_class($this) ." [$method]");
      }


   } // __call

   // Recorre los atributos declarados en hasOne (como "email") y setea los ids (atributos como "email_id')
   public function update_simple_assocs()
   {
      Logger::getInstance()->po_log("PO:update_simple_assocs");

      foreach( $this->hasOne as $attr => $type )
      {
         $obj = $this->attributeValues[ $attr ]; // Valor es un objeto (puede ser null!)

         // El objeto debe estar cargado para poder pedirle el id
         if ( $obj != PersistentObject::NOT_LOADED_ASSOC )
         {
            $refAttrName = DatabaseNormalization::simpleAssoc( $attr );
            if ($obj)
            {
               // seteo "email_id"
               $this->attributeValues[ $refAttrName ] = $obj->getId(); // Seteo tambien "email_id", puede ser NULL !!!
            }
            else
            {
            	$this->attributeValues[ $refAttrName ] = NULL; // Si no hay objeto, la referencia es NULL.
            }
         }
      }
   }



   // Wrapper del PersistencyManager //
   public function save()
   {
      Logger::getInstance()->po_log("PO:save " . get_class($this));

      // Nuevo validation!!
      if (!$this->validate()) return false;

      $this->executeBeforeSave();

      // ===============================================================================================
      // Si esta clase es sublase de otra clase persistente, se deben mergear los atributos de toda
      // la estructura de herencia en una clase persistente "ficticia" y se salva esa clase persistente.
      // Para esto se llama a "getInheritanceStructurePersistentObject".
      // CAMBIO: NO ESTO NO VA ACA!!!

      PersistentManager::getInstance()->save($this);

      $this->executeAfterSave();
      
      // Nuevo validation!!
      return true;
   }

/*
   // Dependencia con YuppLoader, para pedirle todas las clases padres.
   private function getInheritanceStructurePersistentObject()
   {
      // OJO, EN REALIDAD ESTA INSTANCIA DEBERIA TENER TODOS LOS ATRIBUTOS YA CARGADOS!!! XQ LO QUE QUIERO SON LOS VALORES!!
      // Y TIENE LOS QUE FUERON DECLARADOS EN ELLA Y LOS QUE FUERON DECLARADOS EN LOS ANCESTROS!!! O SEA QUE ESTO SE HACE
      // CUANDO SE CREA UNA INSTANCIA Y NO CUANDO SE SALVA, Y COMO SE HACE AHI, NO HAY QUE PONER UN SOPORTE ESPECIAL PARA
      // HERENCIA EN EL SAVE !!!!!...
       *
      // ESTO SI SE DEBERIA HACER PARA LA GENERACION DEL ESQUEMA, EN REALIDAD ALGO PARECIDO...

      $ancestors = ModelUtils::getAllAncestorsOf( get_class($this) );

      // creo nueva clase, sin atributos y le voy poniendo los atributos de todas las clases,

      foreach ($ancestors as $parentClass)
      {
         // pide atributos

      }

      // le agrego los atributos de esta clase

      // retorno todo.
   }
*/

   // Intento solucion TICKET #4.1
   // save_object no se fija en si el objeto esta o no salvado (no considera el sess id) que es justo lo que quiero, que salve sea como sea.
   public function single_save()
   {
      Logger::getInstance()->po_log("PO:single_save " . get_class($this));

   	  PersistentManager::getInstance()->save_object( $this, 0 );
   }

   /**
    * Obtiene la instancia de un objeto de la clase dada (por referencia estatica) 
    * con le identificador que se pasa como parametro.
    */
   public static function get( $id )
   {
      Logger::getInstance()->po_log("PersistentObject.get " . self::$thisClass . " " . $id);

      $obj = PersistentManager::getInstance()->get( self::$thisClass, $id );
      return $obj;
   }


   // Los params son para pasarle atributos de paginacion.
   //
   //public static function listAll( $params = array() )
   public static function listAll( $params )
   {
      Logger::getInstance()->po_log("ListAll ". get_class($this));

//      $offset = 0;
//      if (isset( $params['offset'] )) $offset = $params['offset'];
//
//      $max = 0; // Con max en 0
//      if (isset( $params['max'] )) $max = $params['max'];

      // TODO: podria pasarle params a PM.listAll...
      //echo "PO: " . self::$thisClass . " <hr/>";

      // FIXME: PM no necesita una instancia, le puedo pasar la clase derecho.
      $ins = new self::$thisClass();

      // FIXME: Pasarle params con where.
      return PersistentManager::getInstance()->listAll($ins, $params);
   }


   /**
    * @param $c es la condicion que sirve para armar el WHERE de la consulta.
    * @param $params son parametros extra como de paginacion y ordenamiento para armar el LIMIT y ORDER BY de la consulta.
    * @return devuelve todos los elementos de la clase actual que coincidan con el critero de busqueda.
    */
   public static function findBy( Condition $condition, &$params )
   {
      // Verifica argumentos por defecto.
      if (!isset($params['offset'])) $params['offset'] = 0;
      if (!isset($params['max']))    $params['max']    = 10; // Numero por defecto, hardcoded.
      if (!isset($params['sort']))   $params['sort']   = 'id';
      if (!isset($params['dir']))    $params['dir']    = 'asc';

      // TODO:
      // Tengo que modificar la API de listAll para que acepte Conditions,
      // sobre todo hay que ver como le paso sort, dir, offset y max, ver como armo la Condition adentro de PM.listall()
      $pm = PersistentManager::getInstance();

      $ins = new self::$thisClass();

      return $pm->findBy( $ins, $condition, &$params );

   }

   public static function countBy( Condition $condition )
   {
      $pm = PersistentManager::getInstance();

      $ins = new self::$thisClass();

      return $pm->countBy( $ins, $condition );
   }


   /* Fijarse que listAll recibe una Condition, esto es mas por si armo toda una consulta complicada, no se si el lugar sea PO o deba ir derecho a PM.
   public static function findAllWithQuery( Query $q )
   {
   }
   */

   public static function count( $params = array() )
   {
      $ins = new self::$thisClass();
      return PersistentManager::getInstance()->count( $ins );
   }




   // ====================
   // Se puede usar el __call para simular metodos findAllByXXXAndYYY ... en el fondo es un constructor de Query... (xq hay que usar And u Or).
   // ====================


   // ====================== //
   // OPERACIONES DEL MODELO //

   /**
    * Devuelve todos los nombres de atributos hasMany que son de tipo $clazz
    * @param Class $clazz clase por la que se buscan los atributos
    * @return array
    */
   public function hasManyAttributesOfClass( $clazz )
   {
      //echo get_class($this) . " hasManyAttributesOfClass $clazz " . gettype($clazz) ." - ";
      $res = array();
      foreach ($this->hasMany as $attrname => $hmclazz)
      {
         //echo " hmclazz: $hmclazz " . gettype($hmclazz) ." - ";
         if ($clazz === $hmclazz)
         {
            //echo " COINCIDE: $clazz, $hmclazz ";
            $res[] = $attrname;
         }
      }
      return $res;
   }

   /**
    * Devuelve todos los nombres de atributos hasOne que son de tipo $clazz
    * @param Class $clazz clase por la que se buscan los atributos
    * @return array
    */
   public function hasOneAttributesOfClass( $clazz )
   {
      $res = array();
      foreach ($this->hasOne as $attrname => $hmclazz)
      {
         if ($clazz == $hmclazz) $res[] = $attrname;
      }
      return $res;
   }

   /**
    * Devuelve TRUE si los atributos corresponden a la misma relacion entre dos clases. Cada atributo es de una clase.
    * @param string $aAttr atributo de una clase
    * @param string $bAttr atributo de otra clase
    * @return boolean
    */
   public static function attributesOfSameRelationship( $aAttr, $bAttr )
   {
      // Si los nombres de lso atributos tienen la asociacion codificada (luego de "__"), se fija si estas son iguales.
      //echo "<h1>--- attributesOfSameRelationship( $aAttr $bAttr ) ---".strrpos($aAttr, "__")."</h1>";
      $suf1 = substr( $aAttr, strrpos($aAttr, "__") );
      $suf2 = substr( $bAttr, strrpos($bAttr, "__") );
      //echo "SUF1: " . $suf1;
      //echo "SUF2: " . $suf2;
      return ( strcmp($suf1, $suf2) == 0 );
   }

   /**
    * Dado el nombre de un atributo, que potencialmente podria tener codificado el nombre de la relacion, por ejemplo:
    * role__assoc, devuelve solo el nombre del role, si no tiene el nombre de la asociacion, simplemente devuelve el mismo valor.
    */
   public static function getAssocRoleName( $attributeRawName )
   {
      $pos = strrpos($attributeRawName, "__");
      if ( $pos === false )
      {
          return $attributeRawName;
      }
      return substr( $attributeRawName, -$pos);
   }

   /**
    * Esta operacon es para cuando pido asociaciones por el nombrede atributo pero sin el nombre de asociacion,
    * si el nombre completo del atributo es role__assoc y ejecuto la accion obj->getRole() necesito obtener el
    * nombre completo a partir solo del role, para esto el rol no debe repetirse.
    */
   public function getFullAttributename( $attrWithoutAssocName )
   {
      foreach ($this->hasMany as $attr => $clazz)
      {
      	$pos = stripos($attr, $attrWithoutAssocName);
         if ($pos === 0) // veo si el nombre del atributo es prefijo del nombre real, me viene "role" y $attr es "role__assoc".
         {
             return $attr;
         }
      }
      // TODO: creo que no lo necesito para hasOne... verificar. SI, porque cuando se hace setAttr() necesito el nombre del atributo con asociacion.
      foreach ($this->hasOne as $attr => $clazz)
      {
         $pos = stripos($attr, $attrWithoutAssocName);
         if ($pos === 0) // veo si el nombre del atributo es prefijo del nombre real, me viene "role" y $attr es "role__assoc".
         {
             return $attr;
         }
      }
   }

   // La idea es que se invoque sobre el lado debil de una relacion n-n pasandole como parametros la clase
   // del lado fuerte (q son los que tengo al salvar) y el atributo correspondiente de esa clase cuando la relacion es bidireccional.
   // (si no es bidireccional no se deberia llamar a esta funcion).
   public function getHasManyAttributeNameByAssocAttribute( $assocClass, $assocAttribute )
   {
      // Se ejecuta sobre A y se pasa el atributo de B y quiero el nombre del atributo de A corespondiente a ese atributo de B en la relacion.
      // Es para salvar relaciones n-n bidireccionales y saber el tipo de la instancia, si es uni o bi direccional.
      $hmattrs = $this->hasManyAttributesOfClass( $assocClass );

      //print_r( $this->hasMany );
      //print_r( $hmattrs );

      $tam = sizeof($hmattrs);
      if ( $tam == 0 ) return NULL; // throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no tiene un atributo hasMany a " . $assocClass);

      if ( $tam == 1 ) return $hmattrs[0]; // Si hay uno, es ese!

      // Si hay muchos, tengo que ver por el nombre de asociacion codificado en el nombre de los atributos.
      foreach ($hmattrs as $attrName)
      {
      	// attrName es un atributo hasMany que apunta a assocClass desde la clase de la instancia actual ($this)
         if ( self::attributesOfSameRelationship( $attrName, $assocAttribute ) )
         {
            //echo "<h1>OK attributesOfSameRelationship( $attrName $assocAttribute )</h1>";
            return $attrName;
         }
         else
         {
            //echo "<h1>NO NO NO attributesOfSameRelationship( $attrName $assocAttribute )</h1>";
         }
      }
      //throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no existe el atributo hasMany en ". get_class($this) . " correspondiente al atributo " . $assocAttribute . " de " .$assocClass);
      return NULL; // tal vez con retornar NULL alcance... en lugar de exceptuar.
   }

   // Idem para hasOne...
   public function getHasOneAttributeNameByAssocAttribute( $assocClass, $assocAttribute )
   {
      $hmattrs = $this->hasOneAttributesOfClass( $assocClass );
      $tam = sizeof($hmattrs);
      if ( $tam == 0 ) return NULL; // throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no tiene un atributo hasMany a " . $assocClass);

      if ( $tam == 1 ) return $hmattrs[0]; // Si hay uno, es ese!

      // Si hay muchos, tengo que ver por el nombre de asociacion codificado en el nombre de los atributos.
      foreach ($hmattrs as $attrName)
      {
         // attrName es un atributo hasMany que apunta a assocClass desde la clase de la instancia actual ($this)
         if ( self::attributesOfSameRelationship( $attrName, $assocAttribute ) ) return $attrName;
      }
      //throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no existe el atributo hasMany en ". get_class($this) . " correspondiente al atributo " . $assocAttribute . " de " .$assocClass);
      return NULL; // tal vez con retornar NULL alcance... en lugar de exceptuar.
   }


   // Funcion inversa a belongsTo
   public function isOwnerOf( $attr )
   {
      // bool array_key_exists ( mixed $key, array $search )

      $_thisClass = get_class($this); //self::$thisClass; // get_class da PO, deberia usar otro valor y no la clase...

      // Verifico si tengo el atributo y esta en una relacion.
      //array_key_exists ( $attr, $this->attributeTypes ) // Los atributos simples no son asociaciones.
      if ( array_key_exists ( $attr, $this->hasOne ) )
      {
         //Logger::getInstance()->info( "PersistenObject.isownerOf( $attr ) Entra en hasOne." );
         $obj = new $this->hasOne[$attr]();

//         Logger::getInstance()->error( $obj->belonsToClass( get_class($this) ) );

         // SOL TICKET #2
         // Dependiendo del tipo de relacion se si un objeto es duenio de otro:
         // - 1)  Si la relacion es A (1)->(1) B entonces B belongsTo A.
         // - 2)  Si la relacion es A (1)<->(1) B entonces se necesita belongsTo para saber cual es el lado fuerte.
         // - 3)  Si la relacion es A (1)->(*) B entonces B belongsTo A.
         // - 4)  Si la relacion es A (1)<->(*) B entonces B belongsTo A.
         // - 5)  Si la relacion es A (*)->(*) B entonces B belongsTo A.
         // - 6)  Si la relacion es A (*)<->(*) B entonces se necesita belongsTo en alg�n lado.
         //
         // La clase actual es A, el obj es de clase B.

         // Si la relacion es unidireccional, como yo lo tengo, yo soy el duenio... (CONVENSION)
         if ($obj->hasOneOfThis( $_thisClass )) // 2) bidireccional 1..1
         {
            return $obj->belonsToClass( $_thisClass ); // Si el objeto que quiero saber si soy duenio pertenece a mi => si soy duenio de el.
         }
         else // 1) unidireccional 1..1
         {
            return true;
         }
      }
      else if ( array_key_exists ( $attr, $this->hasMany ) )
      {
         //Logger::getInstance()->info( "PersistenObject.isownerOf( $attr ) Entra en hasMany." );

         $obj = new $this->hasMany[$attr]();
         //print_r( $obj );

         if ($obj->hasOneOfThis( $_thisClass )) // 4) bidireccional 1..*
         {
            return true;
         }
         else
         {
            //echo "<h1>PO.isOwnerOf --- VEO HAS MANY ". get_class($obj) ." - ". $this->name .", ". $this->withTable ."</h1>";

            //if ($obj->hasManyOfThis( get_class($this) )) // 6) bidireccional *..*
            if ($obj->hasManyOfThis( $_thisClass  ))
            {
                return $obj->belonsToClass( $_thisClass ); // problema: get_class(this) tira PO...
               //return $obj->belonsToClass( get_class($this) ); // Si el objeto que quiero saber si soy duenio pertenece a mi => si soy duenio de el.
            }
            else // casos 3 o 5, como es unidireccional, toma el control la clase del lado que no es visto de la otra.
            {
               return true;
            }
         }
      }

      // Si llega aca deberia tirar un warning xq el atributo que me pasaron no es de una relacion...
      return false;
   }

   // Simplemente se fija si tengo la clase en la lista de objetos a los que pertenezco.
   // Busqueda simple del valor pasado.
   public function belonsToClass( $className )
   {
      foreach ( $this->belongsTo as $belonsToClass )
      {
         // VERIFY: No se si esto podria tener problemas cuando agregue herencia! (por los nombres de las clases digo...)
         if ( $belonsToClass == $className ) return true;
      }
      return false;
   }

   public function hasAttribute( $attr )
   {
      return array_key_exists ( $attr, $this->attributeTypes );
   }

   // operacion para saber si tengo un objeto de esta clase en hasOne.
   public function hasOneOfThis( $clazz )
   {
      foreach($this->hasOne as $attr => $aClass)
      {
          // VERIFY: No se si esto podria tener problemas cuando agregue herencia! (por los nombres de las clases digo...)
         if ($aClass == $clazz) return true;
      }
      return false;
   }

   public function hasManyOfThis( $clazz )
   {
      foreach($this->hasMany as $attr => $aClass)
      {
          // VERIFY: No se si esto podria tener problemas cuando agregue herencia! (por los nombres de las clases digo...)
         if ($aClass == $clazz) return true;
      }
      return false;
   }

   // /OPERACIONES DEL MODELO //
   // ======================= //


   // Funciones estandar de manejo de objetos persistentes //

   // Ahora esta es setAttributeValue
   public function aSet( $attribute, $value )
   {
//Logger::getInstance()->log( $attribute );
      
      // CODIGO COPIADO DE setAttributeValue.
      // Modificaciones:
      //
      // Chekeo is_scalar para seteo de atributos simples.
      // Se agregaron returns para los casos de seteo correcto.
      // Chekeo de is_null para hasOne.
      // Consideracion de valor null para hasOne.
      //
      
      //Logger::struct( $this->attributeTypes );
      //Logger::struct( $this->attributeValues );

      // VERIFY: CUal es la joda de discutir en que lista esta si al final hago lo mismo ???
      // SIRVE PARA VERIFICAR QUE LO QUE ESTOY SETEANDO ES VALIDO.
      // CHECK 1: El atributo esta en la lista de atributos?
      if ( array_key_exists($attribute, $this->attributeTypes) )
      {
         if ( is_null($value) || is_scalar($value) ) // Dejo tambien setear NULL xq al setear email_id puede ser NULL y un valor simple tambien puede ser NULL si se lo desea.
         {
            $this->attributeValues[ $attribute ] = $value;
            return;
         }
         else
         {
         	throw new Exception( "El valor para el atributo simple $attribute no es simple, es un " . gettype($value) );
         }
      }
      
//Logger::getInstance()->log( $attribute );

      // Para checkear hasOne o hasMany tengo que fijarme por el nombre del atribtuo que puede tener el nombre de la asociacion.
      // $attribute no es atributo simple.
      $full_attribute = $this->getFullAttributename( $attribute ); // Podria tener codificador el nombre de la asociacion.
                                                              // SE PIERDE EL ATRIBUTOOOO SI ES UN ATRIBUTO SIMPLE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

      if ( array_key_exists($full_attribute, $this->hasOne) )
      {

         if ( is_null($value) || is_subclass_of( $value, PersistentObject ) ) // El caso null es valido pero falla en el is_subclass_of, por eso se agrega como OR a la conicion.
         {
            $this->attributeValues[ $full_attribute ] = $value; // email

            // Si seteo NULL no puedo preguntarle el id!!!
            $refAttrName = DatabaseNormalization::simpleAssoc( $full_attribute ); // "email_id"
            if ( $value ) $this->attributeValues[ $refAttrName ] = $value->getId(); // Seteo tambien "email_id", puede ser NULL !!!
            else $this->attributeValues[ $refAttrName ] = NULL; // Seteo tambien "email_id", puede ser NULL !!!

            return;
         }
         else
         {
            throw new Exception( "El valor para el atributo hasOne $full_attribute no es persistente, es un " . gettype($value) );
         }
      }

      if ( array_key_exists($full_attribute, $this->hasMany) ) // El valor deberia ser una lista de objetos....
      {
         // $value Debe ser un array
         // TODO: ademas deberia ser de objetos persistentes.
         // TODO: NULL es un valor valido para una lista de objetos ?
         if ( is_array($value) )
         {
             $this->attributeValues[ $full_attribute ] = $value;
             return;
         }
         else
         {
             throw new Exception("El valor para el atributo ". $full_attribute ." debe ser un array.");
         }
      }
      
      //Logger::getInstance()->log( $attribute );

      throw new Exception("El atributo '". $attribute ."' no existe en la clase (" . get_class($this) . ") @PO.aSet() " . __LINE__);

   } // aSet


   // TODO: Al setear un objeto de hasOne deberia setear el "email_id" (que podria ser null o un entero)...
   // Devuelve valores de atributos, mas estructurado que OO, pero es para uso interno desde DAL por ejemplo.
   public function aGet( $attr ) // Cambie el nombre de get xq se choca con el get que quiero poner de wrapper del PM.
   {
      // Si es un atributo de referencia de aosciacion hasOne (como email_id), me fijo en el id del elemento! me fijo si tengo "email" en hasOne.
      // $refAttrName = DatabaseNormalization::simpleAssoc( $attr );
//      Logger::getInstance()->error( "Pido attr getAttributeValue(): " . $attr);

//      echo "<h1>getAttributeValue getAssocRoleName: $attr</h1>";
//      $attr = self::getAssocRoleName( $attr ); // Podria tener codificador el nombre de la asociacion.
//      echo "<h1>getAttributeValue getAssocRoleName: $attr</h1>";

      // -----------------------------------------

      // Si no es un atributo simple tengo que ver lazy load...
      if ( !array_key_exists($attr, $this->attributeTypes) )
      {
         $attr = $this->getFullAttributename( $attr ); // Podria tener codificador el nombre de la asociacion.

         // Soporte para lazy loading par ahasOne y hasMany
         if ( $this->attributeValues[$attr] == self::NOT_LOADED_ASSOC )
         {
            if ( array_key_exists($attr, $this->hasMany) )
            {
               // VERIFY: en otros lados hago este chekeo: // Si el objeto esta guardado, trae las clases ya asociadas...
               // if ( $this->getId() && $pm->exists( get_class($this), $this->getId() ) )
               // ver si es necesario...

                PersistentManager::getInstance()->get_many_assoc_lazy(&$this, $attr); // El atributo se carga, no tengo que setearlo...
            }
            else if ( array_key_exists($attr, $this->hasOne) )
            {
               // Si hay id de asociacion, lo cargo, si no lo pongo en NULL
               $assocAttr = DatabaseNormalization::simpleAssoc( $attr ); // email_id
               $assocId = $this->attributeValues[ $assocAttr ];
               if ( $assocId != NULL )
                  $this->attributeValues[ $attr ] = PersistentManager::getInstance()->get_object( $this->hasOne[$attr], $assocId );
               else
                  $this->attributeValues[ $attr ] = NULL;
            }
            else
            {
               throw new Exception("El atributo ". $attr ." no existe en la clase (" . get_class($this) . ")");
            }
         } // si no esta cargada
      } // si no es simple

      return $this->attributeValues[ $attr ];

   } // aGet

   public function aContains( $attribute, $value )
   {
   	// TODO:

      // CHEK: Attribute es un atributo hasMany.
      if ( array_key_exists($attribute, $this->hasMany) )
      {
         // Value puede ser: entero (entonces es un id), PO (entonces se compara por su id), Clausura (se hace una busqueda).

         $attr_w_assoc_name = $this->getFullAttributename( $attribute ); // Podria tener codificador el nombre de la asociacion.

          // FIXME: Este codigo se repite en otras operaciones que trabajan sobre atributos hasMany... deberia reusar el codigo y hacer una funcion.
          // Soporte lazy load...
          if ( $this->attributeValues[ $attr_w_assoc_name ] == self::NOT_LOADED_ASSOC )
          {
             $pm = PersistentManager::getInstance();

             // Si el objeto esta guardado, trae las clases ya asociadas, si no, inicializa el vector.

             if ( $this->getId() && $pm->exists( get_class($this), $this->getId() ) )
             {
                $pm->get_many_assoc_lazy( &$this, $attr_w_assoc_name ); // Carga elementos de la coleccion... si es que los hay... y si no inicializa con un array.
             }
             else // Si no esta salvado...
             {
                $this->attributeValues[ $attr_w_assoc_name ] = array(); // Inicializa el array...
             }
          }
      }
      else
      {
      	throw new Exception("El atributo hasMany $attribute no existe en la clase (" . get_class($this) . ")");
      }

      $id = -1;
      if ( is_int($value) ) // Busca por id
      {
         $id = $value;
      }

      if ( is_subclass_of($value, PersistentObject) ) // Busca por id del PO
      {
         $id = $value->getId(); // FIXME: debe tener id seteado!
      }

      if ( $id != -1 )
      {
         // FIXME: El atributo deberia ser un array (capaz puede ser null, tengo que fijarme bien)
         if ($this->attributeValues[$attr_w_assoc_name])
         {
            foreach( $this->attributeValues[$attr_w_assoc_name] as $assocObj )
            {
               if ($assocObj->getId() == $id)
               {
                  return true;
               }
            }
         }
         return false; // no lo encuentra.
      }

      // TODO: else ...
      // TODO: por clausura
      throw new Exception("Tipo de busqueda no soportada, value debe ser un entero o un PersistentObject y su valor es " . print_r($value,true));
   }

   public function aAddTo ($attribute, $value)
   {
      // CHEK: attribute es un atributo hasMany
      // CHEK: value es un PO, TODO: podria pasarle una lista y que agregue todos los elementos.

      $attr_with_assoc_name = $this->getFullAttributename( $attribute ); // Podria tener codificador el nombre de la asociacion.

      // TODO: Se podria poner la restriccion de que no se puede hacer set('id', xxx); 
      // o sea el id no se puede modificar por el usuario.
      // (asi puedo asumir que si no tiene id es xq no esta guardado... y me ahorro consultar si existe en la base)

      // CHECK 1: El atributo esta en la lista de atributos hasMany
      if ( array_key_exists($attr_with_assoc_name, $this->hasMany) )
      {
         // El valor es un array. (ya deberia estar inicializado, pero chequearlo no esta de mas, igual por ahora no lo chekeo)
         // TODO: si se pone soporte para sets, habria que chequear que el objeto no esta ya en la lista.
         // FEATURE: Para las relaciones 1..* y *..* la implementacion puede ser de lista o de set.
         //          Con lista, cada elemento tiene un indice.
         //          Con set, no hay elementos repetidos.

         if ( $this->attributeValues[ $attr_with_assoc_name ] == self::NOT_LOADED_ASSOC )
         {
            $pm = PersistentManager::getInstance();
            // Si el objeto esta guardado, entonces trae las clases ya asociadas...
            if ( $this->getId() && $pm->exists( get_class($this), $this->getId() ) )
            {
               $pm->get_many_assoc_lazy( $this, $attr_with_assoc_name ); // Carga elementos de la coleccion... si es que los hay... y si no inicializa con un array.
            }
            else // Si no esta salvado y el hasMany esta marcado como not loaded...
            {
               $this->attributeValues[ $attr_with_assoc_name ] = array(); // Inicializa el array...
            }
         }

         // TODO: permitir que value sea un array y agregar cada objeto... (sin chekear repetidos)


         // Chekeo de tipos con el tipo definido en hasMany para este atributo.
         
         // Si es colection, se agrega normalmente, 
         // si es set se verifica que no hay otro con el mismo id, 
         // si es list al salvar y cargar se respeta el orden en el que se agregaron los elementos.
         
         switch ( $this->hasManyType[$attribute] )
         { 
            case self::HASMANY_COLECTION:
            
               $this->attributeValues[ $attr_with_assoc_name ][] = $value; // TODO: Verificar que args0 es un PersistentObject y es simple!
                                                                           // FIX: bool is_subclass_of ( mixed $object, string $class_name )
            break;
            case self::HASMANY_SET: // Buscar repetidos por id, si ya esta no agrego de nuevo.
            
               $found = false;
               reset( $this->attributeValues[ $attr_with_assoc_name ] );
               $elem = current( $this->attributeValues[ $attr_with_assoc_name ] );
               while ( $elem )
               {
               	if ($elem->getId() === $value->getId() )
                  {
                     $found = true;
                     break; // while
                  }
                  
                  $elem = next( $this->attributeValues[ $attr_with_assoc_name ] );
               }
               
               // Agrega solo si ya no esta.
               if (!$found)
               {
               	$this->attributeValues[ $attr_with_assoc_name ][] = $value; // TODO: Verificar que args0 es un PersistentObject y es simple!
                                                                              // FIX: bool is_subclass_of ( mixed $object, string $class_name )
               }
            
            break;
            case self::HASMANY_LIST: // Por ahora hace lo mismo que COLECTION, en PM se verificaria el orden.
            
               $this->attributeValues[ $attr_with_assoc_name ][] = $value; // TODO: Verificar que args0 es un PersistentObject y es simple!
                                                                           // FIX: bool is_subclass_of ( mixed $object, string $class_name )
            break;
         }
         
      }
      else
      {
         throw new Exception("El atributo $attribute no existe en la clase (" . get_class($this) . ")");
      }

   }

   public function aRemoveFrom ($attribute, $value, $logical = false)
   {
      // CHEK: attribute es un atributo hasMany
      // CHEK: value es un PO, TODO: podria pasarle una lista y que remueva todos los elementos.
      $attr = self::getAssocRoleName( $attribute ); // Podria tener codificador el nombre de la asociacion.

      // CHECK 1: El atributo esta en la lista de atributos hasMany
      if ( array_key_exists($attr, $this->hasMany) )
      {
         $pm = PersistentManager::getInstance();

         // FIXME: Este codigo se repite en otras operaciones que trabajan sobre atributos hasMany... 
         // deberia reusar el codigo y hacer una funcion.
         // Soporte lazy load...
         if ( $this->attributeValues[ $attr ] == self::NOT_LOADED_ASSOC )
         {
            // Si el objeto esta guardado, trae las clases ya asociadas, si no, inicializa el vector.

            if ( $this->getId() && $pm->exists( get_class($this), $this->getId() ) )
            {
               $pm->get_many_assoc_lazy( &$this, $attr ); // Carga elementos de la coleccion... si es que los hay... y si no inicializa con un array.
            }
            else // Si no esta salvado...
            {
               $this->attributeValues[ $attr ] = array(); // Inicializa el array...
            }
         }

         // =================================================================
         // Aqui llega con la coleccion cargada o inicializada, siempre!
         // =================================================================

         // Si la coleccion no tiene elementos no hace nada.
         if ( count($this->attributeValues[ $attr ]) > 0 )
         {
            // Idem a *Contains
            $id = -1;
            if ( is_int($value) ) // Busca por id
            {
               $id = $value;
            }

            if ( is_subclass_of($value, PersistentObject) ) // Busca por id del PO
            {
               $id = $value->getId(); // TODO CHECK: debe tener id seteado!
            }

            // TODO: porque el valor del id seria -1? Seria si el objeto todavia no has sido guardado?
            if ( $id != -1 )
            {
               // Busco en atributos hasMany attr y si encuentro elimino.
               $hmList = $this->attributeValues[$attr];
               foreach ( $hmList as $i => $obj )
               {
                  // Busco por id.
                  // FIXME: el objeto DEBE tener id! (si lo cargue lazy tiene id, si no, tengo que guardarlo antes de preguntar por id!...)
                  if ( $obj->getId() == $id ) // FIXME: OJO COMPARACION DE OBJETOS... DEBERIA COMPARAR ids?
                  {
                     // TODO: debe actualiza la tabla de relacion, eliminando la relacion persistida!
                     $this->attributeValues[$attr][$i] = null;
                     $this->attributeValues[$attr] = array_filter($this->attributeValues[$attr]); // La forma PHP de hacerlo... array sin NULLs...

                     // Actualizo la base:
                     //remove_assoc( $obj1, $obj2, $attr1, $attr2, $logical = false );

                     // TODO: Verificar si el nombre de este atributo es el correcto!
                     // Dado el otro objeto y mi atributo, quiero el atributo del otro objeto que corresponda a la relacion con mi atributo.
                     $attr2 = $obj->getHasOneAttributeNameByAssocAttribute( get_class($this), $attr );
                     if (!$attr2) $attr2 = $obj->getHasManyAttributeNameByAssocAttribute( get_class($this), $attr );
                     // FIXME: Problema si el atributo es hasOne! no encuentra el nombre del atributo!
                     // TODO: La operacion deberia ser para los 2 lados y ser tanto para n-n como para 1-n

                     // FIXME: Si la relacion es 1<->* deberia setear en NULL el lado 1 (ya lo mencione en otro lugar...) y salvar ese objeto.

//                     echo '<h1 style="color:red;">OBJ1:  '. get_class($this)  .'</h1>';
//                     echo '<h1 style="color:red;">OBJ2:  '. get_class($obj)   .'</h1>';
//                     echo '<h1 style="color:red;">ATTR1: '. $attr  .'</h1>';
//                     echo '<h1 style="color:red;">ATTR2: '. $attr2 .'</h1>';

                     // Por defecto la asociacion se borra fisicamente.
                     $pm->remove_assoc( $this, $obj, $attr, $attr2, $logical ); // TODOL: Ok ahora falta hacer que el get considere asociaciones solo con daleted false cuando carga.

                     return;
                  }
               }
            } // Si el elmento esta en la coleccion (necesito el ID!)
         } // si la coleccion no es vacia
      }
      else
      {
         throw new Exception("El atributo ". $attr ." no existe en la clase (" . get_class($this) . ")");
      }
   }


   /**
    * Elimina un elemento de la base de datos, eliminacion fisica por defecto.
    * @param boolean $logical indica si la eliminacion es logica (true) o fisica (false).
    * @todo: hacer delete por clase/id, esta es solo por instancia.
    */
   public function delete( $logical = false )
   {
      Logger::add( Logger::LEVEL_PO, "PO::delete " . __LINE__ );
      
      // FIXME: si no esta salvado (no tiene id), no se puede hacer delete.
      $pm = PersistentManager::getInstance();
      $pm->delete( $this, $this->getId(), $logical ); // FIXME: no necesita pasarle el id, el objeto ya lo tiene...
   }
   
   
   /**
    * Si $resursive es true, se cargan las asociaciones de la clase y tambien se pasan a json.
    */
   public function toJSON( $recursive = false )
   {
      // TODO: falta el objeto en si y el JSON de las relaciones 1 y many, para many debe ser 
      // un array por nombre de atributo y un array de objetos de la lista many, para lso 
      // objetos es un array por nombre del atributo.
      
   	$json = "{'attributes' : {";
      
      //echo "<pre>";
      //print_r( $this->attributeTypes );
      //echo "</pre>";
      $i = 0;
      $n = count($this->attributeTypes)-1;
      foreach ( $this->attributeTypes as $attr => $type )
      {
      	$json .= "'" . $attr ."' : '" . $this->aGet($attr) . "'";
         
         
         if ($i<$n) $json .= ", ";  
         $i++;
      }
      
      $json .= "}}";
      
      // TODO: if $recursive ....

      return $json;
   }


   // Operadores sobre POs como conjuntos de atributos
   
   /**
    * Devuelve un PO con los atributos de $po1 que no estan en $po2.
    * El resultado es un
    */
   public static function less( $po1, $po2 )
   {
      // FIXME: faltan atributos hasOne y hasMany!!! tambien contraints xq afectan la generacion del esquema!!!
      
      // ***************************************************************
      // ***************************************************************
      // ***************************************************************
      $class = $po1->getClass();
      $res   = new $class(); // si hago una instancia de esta clase estoy en la misma, genera los atributos de la superclase...
      //$res = new PersistentObject(); // FIXME: deberia ser instancia de la clase y la forma de hacer la resta seria mediante un remove de cada atributo (y no tengo esas operaciones!)
      
      $hone  = $po1->getHasOne();
      $hmany = $po1->getHasMany();
      //$constraints = $sc_ins->getConstraints();
      //foreach( $constraints as $attr => $constraintList ) $c_ins->addConstraints($attr, $constraintList);
      foreach( $po1->getAttributeTypes() as $name => $type )
      {
         //if ( !$po2->hasAttribute($name) ) $res->addAttribute($name, $type);
         // Si el atributo es inyectado no lo saco!
         if ( $po2->hasAttribute($name) && !$po2->isInyectedAttribute($name)) $res->removeAttribute($name); // le saco al po1 los atributos de po2 si es que los tiene...
         
         // Como po1 tiene un merge de los atributos de las subclases que se mapean en la misma tabla que po1, 
         // tengo que agregar los atributos que faltan en la instancia res pero estan en po1.
         // Y tengo que ver que no este en po2 xq si no le estoy metiendo el atributo que quiero eliminar...
         if ( !$res->hasAttribute($name) && !$po2->hasAttribute($name)  ) $res->addAttribute($name, $type);
         
      }
      //foreach( $hone as $name => $type )  $c_ins->addHasOne($name, $type);
      //foreach( $hmany as $name => $type ) $c_ins->addHasMany($name, $type);
   	
//      echo "<pre><h1>";
//      print_r($res);
//      echo "</h1></pre>";
      
      return $res;
   }


} // PersistenObject
// ===================================================================================================

// Para modelar la relacion 1..* con una tabla intermedia.

class ObjectReference extends PersistentObject {

   // Valores posibles para el tipo. Atributos no persistentes!!!
   // Indican la direccion de la relacion (sirve para hacer determinista el tener relaciones *-* asi se en que sentido se puede recorrer la asociacion).
   const TYPE_ONEDIR = 1; // Solo el fuerte al debil.
   const TYPE_BIDIR  = 2; // Fuerte a debil y debil a fuerte.

   public function __construct( $args = array() )
   {
      $this->attributeTypes  = array(
                                     "owner_id"  => Datatypes::INT_NUMBER,
                                     "ref_id"    => Datatypes::INT_NUMBER,
                                     "type"      => Datatypes::INT_NUMBER,
                                     "ord"       => Datatypes::INT_NUMBER // FIXME: si lo declaro aqui, y el tipo de la relacion no es lista, 
                                                                          // me genera la consulta con ORD y la consulta me tira el error de 
                                                                          // que el atributo no existe, en realidad se deberia enchufar
                                                                          // dinamicamente el atributo si es que la coleccion es una lista o
                                                                          // se genera siempre la tabla con el atributo ORD y se pone en null
                                                                          // si no es lista.
                                    );

      // Aca se pueden cargar valores por defecto!
      $this->attributeValues = array(
                                     "owner_id"  => NULL,
                                     "ref_id"    => NULL,
                                    );

      $this->constraints     = array(
                                     "owner_id"  => array( Constraint::nullable(false) ),
                                     "ref_id"    => array( Constraint::nullable(false) ),
                                     "ord"       => array( Constraint::nullable(true) ) // Si el atributo hasMany no es LIST, aca se guarda NULL.
                                    );

      parent::__construct( $args );
   }
}


//
// Genera una tabla intermedia para relaciones 1..* o *..* para modelar la relacion.
//
/*
class PersistentList {

   private $owner; // Clase que tiene la lista (caso 1-*), parte no "belongsTo" (caso *-*)
   private $obj;   // Clase del objeto de la lista

   private $_list; // LISTA DE OBJ... SE LE SETEAN AL OWNER, en una relacion *..* puede ser una lsita de cualquiera de los 2, dependiendo de donde se tiene la vista.
                   // en 1..* el lado * tiene un back link al 1...

   public function __construct()
   {
      $this->withTable = PersistentManager::tableName( $this->owner ) . "_" . PersistentManager::tableName( $this->obj );
   }
}
*/

// ===================================================================================================




?>