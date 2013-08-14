<?php

/**
 * Este archivo contiene la definicion de la clase persistente, que tiene soporte para delaciones 
 * unidireccionales y bidireccionales 1-1, 1-n, n-n. Tambien soporta herencia.
 * 
 * Created on 15/12/2007
 * Modified on 13/06/2010
 * 
 * @name core.persistent.PersistentObject.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.9.0
 * @package core.persistent
 * 
 * @link http://www.simplewebportal.net/yupp_framework_php_doc/2_modelo.html
 */

// FIXME: sacar esto y ponerle LoadClass.
include_once 'core/validation/core.validation.Constraints.class.php';
include_once 'core/validation/core.validation.Errors.class.php';
include_once 'core/utils/core.utils.Callback.class.php';

YuppLoader :: load('core.config', 'YuppConventions');
YuppLoader :: load('core.basic', 'String');
YuppLoader :: load('core.db', 'DAL'); // declara tambien DatabaseNormalization
YuppLoader :: load('core.db', 'Datatypes');
YuppLoader :: load('core.persistent', 'PersistentManager');

/**
 * Esta clase implementa toda la logica necesaria para modelar objetos persistentes.
 * @package core.persistent
 * @subpackage classes
 */
class PersistentObject {

   const NOT_LOADED_ASSOC = -1; // Codigo de asociacion no cargada, util para lazy loading.

   // Tipos de hasMany
   const HASMANY_COLLECTION = 'collection';
   const HASMANY_SET        = 'set';
   const HASMANY_LIST       = 'list';

   // Necesario para poder llamar a las funciones CRUD de forma estatica.
   //protected static $thisClass; // auxiliar para metodos estaticos...
  
   // No se guarda la instancia de PM porque genera problemas con las aplicaciones que usan scaffolding dinamico, porque la app es core pero
   // la app real es distinta, esto se chequea en el constructor del PM, y necesito actualizar el contexto para que PM se de cuenta que es
   // otra la aplicacion real y no core, para que cargue su config de DB en lugar de usar la config por defecto.  
   
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

   // Validacion.
   protected $constraints = array();     // Array de Array de constraints, dado que varias constraints se pueden aplicar al mismo campo.
   
   // Se inicializa en core.validation.Errors en el constructor o antes de validar
   protected $errors; // = array();          // Mensajes de validacion, misma estructura que constraints.
   protected $validated = false;         // Bandera que indica si fue validada con validate()
   
   // Optimizaciones para save y update
   protected $dirty = false;             // Bandera que indica si una instancia cargada desde la base fue modificada.
                                         // Se considera que ls instnacias que fueron creadas y no guardadas (no tienen
                                         // id asignado) son tambien dirty aunque el campo "dirty" sea false.
   protected $dirtyOne  = false;
   protected $dirtyMany = false;         // Marca una modificacion en las relaciones hasMany, pero no indica que los campos
                                         // o asociaciones hasOne fueron modificados, para eso se usa el $dirty.
   // /Optimizaciones para save y update



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
      // Este atributo lo inyecto aunque la instancia sea simple, porque se utiliza en el YuppConventions::tableName.
      // 5: Nombre de la clase, para soporte de herencia.
      $this->attributeTypes["class"]  = Datatypes::TEXT; // Los tipos de los deleted son boolean.
      $this->attributeValues["class"] = get_class($this); // No esta borrado.

      // 4: Inyecta el atributo deleted de tipo boolean.
      $this->attributeTypes["deleted"]  = Datatypes::BOOLEAN; // Los tipos de los deleted son boolean.
      $this->attributeValues["deleted"] = false; // No esta borrado.

      $this->errors = new Errors(); // core.validate.Errors

      // Si es simple, no hago nada.
      if ( $isSimpleInstance ) return;
      
      // Inyecta atributos de referencia hasOne.
      // VERIFY: Ojo, yo puedo tener un hasOne, pero el otro puede tener un hasMany con migo adentro! o sea *..1 bidireccional!!!!! no 1..1
      // 1: Se fija todas las relaciones 1??..1 a otras clases persistentes y agrega lso atributos de FK como "email_id".
      //    Si se hace getEmailId, se devuelve el valor del id ese atributo, si es que no es null. TODO: Modificar __call.
      
      foreach ( $this->hasOne as $attr => $type )
      {
         // Se fija que la declaracion del hasOne sea a un PO.
         /* esta verificacion se hace en addHasOne
            if ( !is_subclass_of($type, 'PersistentObject') )
            {
               throw new Exception("HasOne, atributo $attr del tipo $type no es persistente.");
            }
         */
            
         $newAttrName = DatabaseNormalization::simpleAssoc( $attr ); // Atributo email_id inyectado!
         $this->attributeTypes[ $newAttrName ]  = Datatypes::INT_NUMBER;  // Los tipos de los ids son int.
         $this->attributeValues[ $newAttrName ] = NULL; // FIXME: Esto no es un objeto, es su Id, por eso le pongo NULL y no NOT_LOADED.

         // Inyecto el atributo "email" y lo inicializo en NOT_LOADED...
         $this->attributeValues[ $attr ] = self::NOT_LOADED_ASSOC;

         // ningun objeto asociado, pero en este caso es que el objeto no esta ni siquiera cargado, para poner NULL habria que ver si
         // hay algun objeto y constatar de que no hay ninguno.
      }

      // 2: Inicializo los arrays para los valores de los objetos de los que se tienen varios.
      // FIXME: si en args viene un array con POs para un atributos hasMany, tengo que setearlo... y verificar q es un array y verificar que lo que tiene el array son objetos del tipo declarado en el hasMany.
      // No es necesario el chequeo porque es un array vacio.
      //foreach ( $this->hasMany as $attr => $type )
      //{
         // Esta verificacion ya se hace en addHasMany
         //if ( is_subclass_of($type, 'PersistentObject') )
         //{
         
         // TODO: esta inicializacion se podria hacer en el addHasMany
         // TODO: OJO! puede haber operaciones ahora que piensan que esto es un array y no consideran en NOT LOADED...
         //$this->attributeValues[ $attr ] = self::NOT_LOADED_ASSOC;
         
         //}
         // Else: podria ver como manejar listas de objetos simples como enteros y strings.
         // OJO, las listas de atributos simples no se si estaria bien declararlas en hasMany!
      //}

      // debe ir aqui porque si me viene un valor para un atributo inyectado y hago esto luego, 
      // no me va a poner el valor xq el atributo no estaba inyectado!

      // 3: Inyecta el atributo id de tipo int.
      $this->attributeTypes['id']  = Datatypes::INT_NUMBER; // Los tipos de los ids son int.
      $this->attributeValues['id'] = NULL; // No tengo ningun objeto asociado.


      // Me fijo si en args viene algun valor de atributo
      // FIXME: no deberia poder modificar valor de atributos inyectados, el comportamiento posterior es impredecible.
      foreach ( $args as $attr => $value )
      {
         // Si es inyectado, no se deberia setear con los params, por ejemplo si viene un 'id'.
         if ($this->isInyectedAttribute($attr)) continue;
         
         // FIXME: hace lo mismo que setProperties pero distinto
         if (isset($this->attributeTypes[$attr]) || array_key_exists($attr, $this->attributeTypes))
         {
            // Si es un valor simple y string, lo limpio por espacios extras.
            // Sino es string, al hacerle trim lo transforma a string y genera errores de tipo.
            //$this->attributeValues[$attr] = ((is_string($value))? trim($value) : $value); 
            
            // Si es una instancia nueva, siempre estara dirty si le pongo valores simples.
            $this->dirty = true;
         }
         else if (isset($this->hasOne[$attr]) || array_key_exists($attr, $this->hasOne))
         {
            if (!is_subclass_of($value, 'PersistentObject')) throw new Exception('Se espera un valor de tipo PersistentObject para el atributo '.$attr);
            
            //$this->attributeValues[$attr] = $value;
            
            // Si es una instancia nueva, siempre estara dirty si le pongo valores simples.
            $this->dirtyOne = true;
         }
         else if (isset($this->hasMany[$attr]) || array_key_exists($attr, $this->hasMany))
         {
            if (!is_array($value)) throw new Exception('Se espera un valor de tipo array para el atributo '.$attr);
            
            //$this->attributeValues[$attr] = $value;
            
            // Si es una instancia nueva, siempre estara dirty si le pongo valores simples.
            $this->dirtyMany = true;
         }
         
         $this->attributeValues[$attr] = ((is_string($value))? trim($value) : $value);
      }
   } // construct

   
   /**
    * desde PHP 5.3 con late static bidings se puede obtener el nombre de la
    * subclase real y no el de la clase a la que pertenece el metodo invocado.
    * http://php.net/manual/en/language.oop5.late-static-bindings.php
    * invocaccion: static::sgetClass();
    */
   public static function sgetClass()
   {
      return __CLASS__;
   }
   
   

   /**
    * Apaga las banderas de dirty, se usa para luego de cargar de la base,
    * porque en el proceso de carga prende las banderas, pero es solo
    * una carga de lo que hay, no un cambio real que haga dirty a
    * la instancia de PO.
    */
   public function resetDirty()
   {
      $this->dirty = false;
      $this->dirtyOne = false;
      $this->dirtyMany = false;
   }
   public function resetDirtyMany()
   {
      $this->dirtyMany = false;
   }
   public function isClean()
   {
      return !($this->dirty || $this->dirtyOne || $this->dirtyMany );
   }
   public function isDirty()
   {
      return $this->dirty;
   }
   public function isDirtyOne()
   {
      return $this->dirtyOne;
   }
   public function isDirtyMany()
   {
      return $this->dirtyMany;
   }

   /**
    * Agrega una lista de restricciones a un atributo de la clase, 
    * para cuando se asigne un valor al atributo, verificar que el valor es correcto.
    */
   public function addConstraints( $attr, $constraints )
   {
      // Check 1: el atributo existe.
      if (!$this->hasAttribute($attr)) throw new Exception("Se intenta definir una restriccion en [". get_class($this) ."] para un atributo que no existe ($attr) " . __FILE__ . " " . __LINE__);
      
      // Check 2: constraints debe ser un array.
      if (!is_array($constraints)) throw new Exception("El parametro 'constraints' debe ser un array " . __FILE__ . " " . __LINE__);
      
      // TODO: CHECK 3: constraints debe ser un array de restricciones (subclases de Constraint).
      
      // Si ya hay constraints para ese atributo, no las redefine.
      // Deberia chequear por tipo de cosntraint tmb? asi puedo definir constraints para 
      // distintos atributos en distintas clases en la jerarquia de herencia?
      
      // Quiero que las redefiniciones de restricciones sobreescriban las viejas
      // (no se verifica que ya exista una restriccion para el campo).
      // El problema es si una subclase define una restriccion para un atributo
      // de la superclase y esta ya tiene una restriccion definida.
      $this->constraints[$attr] = $constraints;
   }
   
   /**
    * Obtiene todas las restricciones si $attr es null, o si no,
    * obtiene las restricciones para el atributo $attr.
    */
   public function getConstraints( $attr = NULL )
   {
      if ( $attr === NULL ) return $this->constraints;
      if ( isset($this->constraints[ $attr ]) ) return $this->constraints[ $attr ];

      return array(); // No tiene restricciones
   }
   
   /**
    * Devuelve la restriccion para el atributo que sea del tipo dado, si no la encuentra retorna NULL.
    */
   public function getConstraintOfClass( $attr, $class )
   {
      foreach ( $this->getConstraints($attr) as $constraint )
      {
         if ( get_class($constraint) === $class ) return $constraint;
      }
      return NULL;
   }
   
   /**
    * True si el atributo es declarado en esta clase, false en otro caso
    * (p.e. el atributos es declarado en una superclase y esta lo hereda).
    */
   public function attributeDeclaredOnThisClass( $attr )
   {
      // Si la instancia ni siquiera tiene el atributo, retorna false.
      if ( $this->getAttributeType( $attr ) === NULL ) return false;
      
      $_super = get_parent_class( $this );
      
      // Si la instancia tiene el atributo y el padre es PO, el atributo se declaro en ella.
      if ( $_super === 'PersistenObject' ) return true;
      
      // Si la instancia tiene el atributo y el padre no es PO, tengo que ver si el padre tiene el atributo.
      $_superInstance = new $_super(NULL, true);
      
      // Si el padre NO tiene el atributo, esta declarado en 'esta' clase.
      if ( $_superInstance->getAttributeType( $attr ) === NULL ) return true;
      
      return false;
   }
   
   /**
    * Obtiene la superclase de esta donde fue declarado el atributo.
    * Si no encuentra el atributo, devuelve null.
    */
   public function getSuperClassWithDeclaredAttribute( $attr )
   {
      $superClasses = ModelUtils :: getAllAncestorsOf( $this->getClass() );
      foreach ($superClasses as $superClass)
      {
         $superInstance = new $superClass(NULL, true);
         if ( $superInstance->attributeDeclaredOnThisClass( $attr ) ) return $superClass;
      }
      return NULL; // El atributo no fue declarado en ninguna superclase.
   }

   /**
    * Agrega un atributo a la clase. Es utilizado en la definicion del 
    * modelo para saber que columnas crear en las tablas donde se guardan 
    * las instancias del modelo.
    */
   public function addAttribute($name, $type)
   {
      $this->attributeTypes[$name] = $type;
      $this->attributeValues[$name] = NULL; // Inicializacion de campo simple, garantiza que siempre hay un valor para la key $name
   }
   
   /**
    * Se utiliza en la generacion del esquema para soportar herencia en multiples tablas.
    */
   private function removeAttribute( $attr )
   {
      if (isset($this->attributeTypes[$attr]) || array_key_exists($attr, $this->attributeTypes))
         unset( $this->attributeTypes[$attr] ); // forma de remover un valor de un array...
   }
   
   /**
    * Agrega un atributo de relacion hasOne a la clase, se utiliza para crear una relacion
    * desde esta clase con un elemento de otra clase.
    * 
    * @param String name nombre del atributo hasOne
    * @param String clazz nombre de la clase que se quiere asociar
    */
   public function addHasOne( $name, $clazz, $relName = NULL )
   {
      if (!is_subclass_of( $clazz, 'PersistentObject')) throw new Exception("La clase $clazz del atributo $name debe ser subclase de PersistentObject");
      
      // TEST:
      // El nombre de la relacion relName se usa cuando hay multiples hasMany declarados hacia la
      // misma clase, si esa clase tiene relaciones con esta (o sea bidireccional).
      // Asi se puede saber que rol de una clase correponde al rol en la clase asociada, porque
      // se tiene el mimo nombre de relacion codificado en el rol.
      if ($relName != NULL) $name .= '__'.$relName; // rol__relName
      
      // TODO: habria que verificar que en la clase asociada hay una relacion con el mismo nombre declarada con clase this->getClass.
      
      
      $this->hasOne[$name] = $clazz;
      $this->attributeValues[$name] = self::NOT_LOADED_ASSOC; // Se inicializa como no cargada, luego en el constructor se le puede setear un valor.
   }
   
   /**
    * Agrega un atributo hasMany a la instancia de la clase persistente. Se utiliza para crear
    * una relacion desde esta clase a varios elementos de otra clase.
    * 
    * @param String name nombre del atributo hasmany
    * @param String class clase de los elementos contenidos en la coleccion de elementos
    * @param String type tipo del atributo hasMany, dice si se comporta como una coleccion, un conjunto o una lista
    */
   public function addHasMany( $name, $clazz, $type = self::HASMANY_COLLECTION, $relName = NULL )
   {
      if (!is_subclass_of( $clazz, 'PersistentObject')) throw new Exception("La clase $clazz del atributo $name debe ser subclase de PersistentObject");

      // TEST:
      // El nombre de la relacion relName se usa cuando hay multiples hasMany declarados hacia la
      // misma clase, si esa clase tiene relaciones con esta (o sea bidireccional).
      // Asi se puede saber que rol de una clase correponde al rol en la clase asociada, porque
      // se tiene el mimo nombre de relacion codificado en el rol.
      if ($relName != NULL) $name .= '__'.$relName; // rol__relName
      
      // TODO: habria que verificar que en la clase asociada hay una relacion con el mismo nombre declarada con clase this->getClass.
      
      $this->hasMany[$name] = $clazz;
      $this->hasManyType[$name] = $type;
      $this->attributeValues[$name] = self::NOT_LOADED_ASSOC; // Se inicializa como no cargada, luego en el constructor se le puede setear un valor.
   }
   
   /**
    * Devuelve el tipo de la relacion hasMany de nombre attr. El tipo puede ser LIST, COLECTION o SET.
    * Para obtener la clase de la coleccion hasMany se hace con getAttributeType(attr)
    * @param String attr nombre del atributo hasMany
    */
   public function getHasManyType( $attr )
   {
      if ( $this->hasMany[$attr] === NULL) throw new Exception("La clase no tiene un atributo hasMany con nombre '$attr' " . __FILE__ . " " . __LINE__);

      return $this->hasManyType[$attr];
   }
   
   /**
    * Busca el nombre del atributo por el nombre de la coumna que le corresponde en el ORM.
    * Devuelve NULL si no lo encuentra.
    * Solo busca en atributos simples o hasOne, ya que los hasMany no se 
    * mapean en la misma tabla con una columna (aunque los hasOne solo 
    * seria el atributo referencia que esta tambie en attributeTypes
    * con los attrs simples).
    */
   public function getAttributeByColumn( $colname )
   {
      // Si esta con el mismo nombre, lo retorno (son la mayoria de los casos)
      if ( array_key_exists( $colname, $this->attributeTypes ) ) return $colname;
      
      // Si no esta por el nombre exacto, busco normalizando los nombres de
      // los atributos por la columna que le toca en el ORM.
      foreach ( $this->attributeTypes as $classAttr => $type )
      {
         if ( DatabaseNormalization::col($classAttr) == $colname ) return $classAttr;
      }
      
      // Si no encuentra, devuelve NULL
      return NULL;
   }
   
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

      foreach ( $this->afterSave as $cb ) $cb->execute();

      // Una vez que termino de ejecutar, reseteo los cb's registrados.
      $this->afterSave = array();
   }
   
   private function executeBeforeSave()
   {
      Logger::getInstance()->po_log("Excecute before save ". get_class($this));

      foreach ( $this->beforeSave as $cb ) $cb->execute();

      // Una vez que termino de ejecutar, reseteo los cb's registrados.
      $this->beforeSave = array();
   }
   // ====================================================


   // Consulta sobre el tipo de atributo: inyectado/no inyectado
   public static function isInyectedAttribute( $attr )
   {
      return ( strcmp($attr, "id") == 0 || strcmp($attr, "deleted") == 0 || strcmp($attr, "class") == 0 );
   }

   /**
    * Setea los valores de los "atributos simples" de la clase cuyos nombres aparecen en el mapping.
    * Los valores que no sean de campos de la clase son ignorados.
    * 
    * @param Map params  mapping nombre campo / valor.
   */
   public function setProperties( ArrayObject $params )
   {
      foreach ( $this->attributeTypes as $attr => $type )
      {
         // 1: fijarse si esta en params
         // 2: verificar si el valor que tiene en params es del mismo tipo que el atributo
         //    - Si el tipo es numerico, y el valor es string, ver que sea un string numerico.  http://www.php.net/is_numeric (ojo con numeros negativos o si empiezan con "." o ",", probar!!!)
         //    - Si el tipo es date o time, y el tipo es un string, ver que el string tiene forma de date o time.
         //    - Distinguir entre valor cero y valor null.

         // TODO: Ver como se podrian tambien setear listas de "objetos simples" (no es la idea que esto setee atributos que son PO, solo atributos simples)
         if (isset($params[$attr]) && !$this->isInyectedAttribute($attr)) // IMPORTANTE: id, class, deleted no se pueden setear por set properties!!!
         {
            // Esto es set$attr pero mas rapido!
            // TODO: Chekeos de tipos...
            // WARNING: Esto solo setea atributos simples! Hay que ver si puedo hacer el tema de setear atributos de clases asociadas... (depende de la notacion de las keys de params)
            // SI HAGO TODO EL CHEKEO EN setAttributeValue, solo llamo a esa y listo...
            
            $this->attributeValues[$attr] = (is_string($params[$attr]) ? trim($params[$attr]) : $params[$attr]);
            
            // Si attr el un id de un hasOne, y viene un string vacio, me tira un error en DatabaseXXX
            // al intentar poner un '' en un campo INT id, pero NULL le puedo poner. Asi que si viene
            // un valor vacio, le pongo NULL.
            if ($this->attributeValues[$attr] === '') $this->attributeValues[$attr] = NULL;
            
            
            // FIXME: deberia garantizar que solo vienen valores simples en params.
            
            // Marco como dirty en atributos simples (asignar en cada loop del for es mas barato
            // que estar chequeando si se modifico un campo y setear afuera del loop).
            $this->dirty = true;
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
      // Prueba para no resetear el WT desde una superclase.
      // http://code.google.com/p/yupp/issues/detail?id=19
      if (!isset($this->withTable)) $this->withTable = $tableName;
   }

   /**
    * Devuelve los valores de todos los atributos.
    */
   public function getAttributeValues()
   {
      return $this->attributeValues;
   }

   /**
    * Devuelve los tipos de los atributos simples.
    */
   public function getAttributeTypes()
   {
      return $this->attributeTypes;
   }

   /**
    * Devuelve el tipo de un atributo, busca en hasOne y hasMany tambien.
    * Se usa en el PM.get para cargar objetos asociados.
    * 
    * @param String attr nombre del atributo para el que se quiere obtener su tipo.
    */
   public function getAttributeType($attr)
   {
      if (isset($this->hasOne[$attr]) || array_key_exists($attr, $this->hasOne))
      {
         return $this->hasOne[ $attr ];
      }
      
      if (isset($this->hasMany[$attr]) || array_key_exists($attr, $this->hasMany))
      {
         return $this->hasMany[ $attr ];
      }
      
      if (isset($this->attributeTypes[$attr]) || array_key_exists($attr, $this->attributeTypes))
      {
         return $this->attributeTypes[ $attr ];
      }
      
      return NULL; // except?? el attr no existe.
   }


   /**
    * Obtiene errores de validacion para los atributos que teniendo 
    * restricciones, se verificaron y fallaron.
    */
   public function getErrors()
   {
      return $this->errors; // core.validation.Errors
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
         $res[] = DatabaseNormalization::simpleAssoc( $attr );
      }
      return $res;
   }

   // Devuelve solo valores de atributos simples.
   public function getSimpleAttrValues()
   {
      $res = array();
      
      // Recorre la definicion de atributos simples, y para cada uno devuelve su valor.
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
         // No se retorna NULL para que PM no guarde NULL
         // El tema es que si se pone un atributo en NULL se deberia actualizar en la base
         if ( $value !== NULL )
         {
            $res[$attr] = $value;
         }
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
            $res[$attr] = array_filter( $objectList ); // Saco nulls del array
         }
      }
      return $res;
   }
   // /VALORES DE ASOCIACIONES ====================================================================

   
   /*
    * Verificacion de restriccioens que afectan la generacion del esquema
    */
   public function nullable( $attr )
   {
      // Atributos inyectados no son nullables.
      if (self::isInyectedAttribute( $attr )) return false;

      if ( DatabaseNormalization::isSimpleAssocName( $attr ) ) // Si es un atributo autogenerado de aosciacion hasOne con otra clase, no va a haber contraint para ella, pero si para el atributo en hasOne que se llama dinstinto...
      {
         $attr = DatabaseNormalization::getSimpleAssocName( $attr );
      }
      if ( isset($this->constraints[ $attr ]) )
      {
         foreach ( $this->constraints[ $attr ] as $constraint )
         {
            if ( get_class($constraint) === 'Nullable' ) return $constraint->getValue();
         }
      }
      return true; // Por defecto es nullable. Es mas facil para generar las tablas, ahora se pone en not null solo si hay una restriccion que lo diga.
   }

   /**
    * Similara a 'validate()', solo que valida los valores de los campos cuyos nombres estan presentes en $attrs.
    * 
    * @param array attrs lista de nombres de atributos a verificar la validez de su valor.
    * @return boolean true si no hubieron errores de validacion, false en caso contrario.
    */
   public function validateOnly($attrs)
   {
      $valid = true;
      $this->errors = new Errors(); // core.validation.Errors

      // FIXME: el primer foreach deberia hacerse sobre $attrs
      foreach ($this->constraints as $attr => $constraintArray) // Para cada campo
      {
         if ( in_array($attr, $attrs) )
         {
            foreach ( $constraintArray as $constraint )
            {
               if ( !$constraint->evaluate( $this->aGet($attr) ) )
               {
                  $valid = false;
   
                  // TODO: Validar asociaciones hasOne !!!  (*****)
                  // Ahora se valida el hasOne solo cuando se salva en cascada y se hace en PM,
                  // sino salva en cascada, no deberia validar (es responsabilidad del programador).

                  // Genera el mensaje de error
                  YuppLoader::load('core.validation','ValidationMessage');
                  $err = ValidationMessage::getMessage( $constraint, $attr, $this->aGet($attr) );

                  $this->errors->add($attr, $err);
               }
            }
         }
      }
      return $valid;
   }
   
   /**
    * Este metodo podra ser implementado por las subclases y se invocara antes de validar.
    * http://code.google.com/p/yupp/issues/detail?id=72 
    */
   protected function preValidate() {}

   /**
    * Valida los valores de los campos del objeto contra las restricciones definidas en el.
    * Si se verifican errores, estos se agregan en el campo 'errors' del objeto.
    * 
    * @return boolean true si no hubieron errores de validacion, false en caso contrario.
    */
   public function validate($validateCascade = false)
   {
      Logger::getInstance()->po_log("PO:validate " . get_class($this));
      
      // Preprocesamiento para validar
      // http://code.google.com/p/yupp/issues/detail?id=72
      $this->preValidate();
      

      // Si tiene restriccion nullable(true) o blank(true) y el valor es nulo o vacio,
      // deberia dar que valida aunque haya otra restriccion que falle para el valor.

      $valid = true;
      
      //$this->errors = array(); // Reset de los errores actuales
      $this->errors = new Errors(); // core.validation.Errors
      
      $this->validated = true; // Se setea al ppio para que la cascada no generar loops de validacion,
                               // asi ve que esta instancia tiene validated en true y no intenta revalidarla.

      if ( is_array($this->constraints) )
      {
         foreach ( $this->constraints as $attr => $constraintArray )
         {
            //Logger::getInstance()->po_log("PO:validate B ($attr)");
            
            // =========================================
            // FIXME: reutilizar validateOnly
            // =========================================
            
            // Si la restriccion es para un hasOne, aunque sea validacion sin cascada,
            // igual con esto pide el valor del hasOne y trata de validarlo contra la restriccion.
            $value = ( (isset($this->attributeValues[$attr]) || array_key_exists($attr, $this->attributeValues)) ? $this->attributeValues[$attr] : NULL );
            
            // ===============================================================
            // TICKET: http://code.google.com/p/yupp/issues/detail?id=20
            // Si el valor es null, pregunta por restriccion nullable,
            // que si da ok, no verifica las demas restricciones.
            // Esto es porque si es nullable(true) y el valor es null,
            // las demas restricciones para el mismo atributo no tienen
            // sentido de verificarse porque es posible que den false
            // (min, inList, etc). Se hace un continue para segur verificando
            // las restricciones de otros atributos.

            if ($value === NULL)
            {
                $nullable = $this->getConstraintOfClass($attr, 'Nullable');
                if (isset($nullable) && $nullable->evaluate($value))
                {
                    continue; // Deja de ver restricciones para el atributo actual y sigue con el siguiente
                }
            }
            else if ($value === '') // Si el valor es vacio, hace lo mismo que nullable por con Blank
            {
                $blank = $this->getConstraintOfClass($attr, 'BlankConstraint');
                if (isset($blank) && $blank->evaluate($value))
                {
                    continue; // Deja de ver restricciones para el atributo actual y sigue con el siguiente
                }
            }

            // Ve el resto de las restricciones
            foreach ( $constraintArray as $constraint )
            {
               // TODO: Cuando se validen relaciones, value podria ser un objeto, si la restriccion se puso para una relacion.
               //       Mas abajo se usa el value para armar el string de error y falla si es objeto no tiene toString.
              
               if (!$constraint->evaluate($value))
               {
                  $valid = false;

                  // Genera mensaje de error
                  YuppLoader::load('core.validation','ValidationMessage');
                  $err = ValidationMessage::getMessage( $constraint, $attr, $value );
                  
                  $this->errors->add($attr, $err);
               }
            }
         }
      }
      
      // http://code.google.com/p/yupp/issues/detail?id=50
      if ($validateCascade)
      {
         foreach ( $this->hasOne as $attr => $clazz )
         {
            $inst = $this->attributeValues[ $attr ];
            if ($inst !== NULL && $inst !== PersistentObject::NOT_LOADED_ASSOC && !$inst->validated)
            {
               if (!$inst->validate(true)) // Sigue validando en cascada
               {
                  $valid = false;
               }
            }
         }
      }

      return $valid;
   }

   /**
    * Cuando hago getXXX o setXXX pasa por aca y se implementa aca, aunque los metodos no existan.
    */
   public function __call( $method, $args )
   {
      // OJO, en verdad si tiene el metodo, ya lo llama y no pasa por __call... esto esta de mas.
      // Si tiene algun metodo transient, lo llama. Puede ser un metodo definido en alguna de las clases que extienden esta.
      if (method_exists($this, $method)) return $this->{$method}( $args );

      // getAttributeFromMethod()
      if ( substr($method,0,3) == "get" )
      {
         $attr = substr($method, 3); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...
         $attr = String::firstToLower($attr);
         return $this->aGet( $attr );
      }
      else if ( substr($method,0,3) == "set" ) // setAttributeName( value )
      {
         $attr = substr($method, 3); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...
         $attr = String::firstToLower($attr);
         $this->aSet( $attr, $args[0] );
      }
      else if ( substr($method,0,5) == "addTo" )
      {
         $attr = substr($method, 5); // El problema es que con "tolower" el atributo "fechaNac" queda como "fechanac" y no lo encuentra...
         $attr = String::firstToLower($attr);
         $this->aAddTo($attr, $args[0]);
      }
      else if ( substr($method,0,10) == "removeFrom" )
      {
         $attr = substr($method, 10);
         $attr = String::firstToLower($attr);
         $this->aRemoveFrom( $attr, $args[0] );
      }
      else if ( substr($method,0,13) == "removeAllFrom" )
      {
         $attr = substr($method, 13);
         $attr = String::firstToLower($attr);
         $this->aRemoveAllFrom( $attr );
      }
      else if ( substr($method,-8) == "Contains" )
      {
         $hasManyAttr = substr($method,0,strlen($method)-8);
         return $this->aContains($hasManyAttr, $args[0]);
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
         if ( $obj !== PersistentObject::NOT_LOADED_ASSOC )
         {
            $refAttrName = DatabaseNormalization::simpleAssoc( $attr );
            if ($obj)
            {
               // Si es MTI, el id de cualquier clase parcial será el mismo.
               $this->attributeValues[ $refAttrName ] = $obj->getId(); // seteo ref_id, ej. "email_id"
            }
            else
            {
               $this->attributeValues[ $refAttrName ] = NULL; // Si no hay objeto, la referencia es NULL.
            }
         }
      }
   } // update_simple_assocs


   // Wrapper del PersistencyManager //
   public function save()
   {
      Logger::getInstance()->po_log("PO:save " . get_class($this));

      if (!$this->validate(true)) return false;
      
      //Logger::getInstance()->po_log("PO:save post validate");

      $this->executeBeforeSave();

      //Logger::getInstance()->on();
      $pm = PersistentManager::getInstance();
      try
      {
         //Logger::getInstance()->po_log("PO:save BEGIN");
         $pm->withTransaction();
         $pm->save($this);
         $pm->commitTransaction();
         //Logger::getInstance()->po_log("PO:save COMMIT");
      }
      catch(Exception $e)
      {
         // TODO: log de $e
         Logger::getInstance()->po_log("PO:save ROLLBACK ". $e->getMessage() ." <pre>". $e->getTraceAsString() ."</pre>");
         
         $pm->rollbackTransaction();
         return false;
      }
      //Logger::getInstance()->off();

      $this->executeAfterSave();
      
      // Validacion
      return true;
   }

   // Intento solucion TICKET #4.1
   // Se usa para salvar objetos en cascada y asegurarse que se salvan.
   // save_object no se fija en si el objeto esta o no salvado (no considera el sess id)
   // que es justo lo que quiero, que salve sea como sea.
   public function single_save()
   {
      //Logger::getInstance()->po_log("PO:single_save " . get_class($this));
      PersistentManager::getInstance()->save_object( $this, 0 );
   }

   /**
    * Obtiene la instancia de un objeto de la clase dada (por referencia estatica) 
    * con le identificador que se pasa como parametro.
    */
   public static function get( $id )
   {
      //Logger::getInstance()->po_log("PersistentObject.get " . self::$thisClass . " " . $id);
      $thisClass = static::sgetClass();
      return PersistentManager::getInstance()->get( $thisClass, $id );
   }

   // Los params son para pasarle atributos de paginacion.
   //
   public static function listAll(ArrayObject $params)
   {
      // FIXME: PM no necesita una instancia, le puedo pasar la clase derecho.
      if ($params === NULL) $params = new ArrayObject();
      $thisClass = static::sgetClass();
      return PersistentManager::getInstance()->listAll(new $thisClass(), self::filtrarParams($params));
   }

   /**
    * @param $c es la condicion que sirve para armar el WHERE de la consulta.
    * @param $params son parametros extra como de paginacion y ordenamiento para armar el LIMIT y ORDER BY de la consulta.
    * @return devuelve todos los elementos de la clase actual que coincidan con el critero de busqueda.
    */
   public static function findBy(Condition $condition, ArrayObject $params)
   {
      if ($params === NULL) $params = new ArrayObject();
      $thisClass = static::sgetClass();
      return PersistentManager::getInstance()->findBy(new $thisClass(), $condition, self::filtrarParams($params));
   }
   
   /*
    * Busca todos los $thisClass tal que $ref_id esta en la tabla de join del atributo hasMany de esta clase.
    * https://code.google.com/p/yupp/issues/detail?id=177
    */
   public static function findReverse($hasManyAttr, $ref_id)
   {
      $searchClass = static::sgetClass();
      $thisIns = new $searchClass(array(), true);
      
      //print_r($thisIns->hasMany);
      Logger::getInstance()->po_log("PersistentObject.findReverse ".$searchClass." ".$hasManyAttr." ".$thisIns->hasMany[$hasManyAttr]." ".$ref_id);
      
      return PersistentManager::getInstance()->findHasManyReverse( $searchClass, $hasManyAttr, $thisIns->hasMany[$hasManyAttr], $ref_id );
   }
   
   /**
    * Verifica los parametros max, offset, sort y dir, que pueden venir en un
    * request incluyendo errores, y devuelve un array de parametros correctos.
    */
   public static function filtrarParams( ArrayObject $params )
   {
      $ret = new ArrayObject( $params->getArrayCopy() );
      
      // 1. Si no hay algun parametro, agregarlo con valor por defecto.
      // 2. Si los parametros enteros vienen con un valor invalido, poner el valor por defecto.
      // 3. Si el parametro dir viene con un valor distinto de asc o desc, poner el valor por defecto (incluye que no venga o que venga con valor vacio).
      
      // Si es vacio, o si no es numerico, o si no es entero
      if (!isset($ret['offset']) || !is_numeric($ret['offset']) || (int)$ret['offset'] != $ret['offset']) $ret['offset'] = 0;
      
      // FIXME: el 500 deberia ser parametrico y cada desarrollador lo debe definir como mas le convenga.
      // Si es vacio, o si no es numerico, o si no es entero, o si max es mas que 500 (ver http://code.google.com/p/yupp/issues/detail?id=91)
      if (!isset($ret['max']) || !is_numeric($ret['max']) || (int)$ret['max'] != $ret['max'] || $ret['max'] > 500) $ret['max'] = 500; // Numero por defecto, hardcoded.
      
      // Si no viene sort, se hace por el atributo id
      if (!isset($ret['sort'])) $ret['sort'] = 'id';
      
      // Si no viene dir o si tiene un valor invalido
      if (!isset($ret['dir']) || ($ret['dir'] != 'asc' && $ret['dir'] != 'desc')) $ret['dir'] = 'asc';
      
      return $ret;
   }

   public static function countBy( Condition $condition )
   {
      $thisClass = static::sgetClass();
      $ins = new $thisClass(); // FIXME: pasarle la clase no la instancia
      return PersistentManager::getInstance()->countBy( $ins, $condition );
   }

   public static function count()
   {
      $thisClass = static::sgetClass();
      $ins = new $thisClass(); // FIXME: pasarle la clase no la instancia
      return PersistentManager::getInstance()->count( $ins );
   }

   // ====================== //
   // OPERACIONES DEL MODELO //

   /**
    * Devuelve todos los nombres de atributos hasMany que son de tipo $clazz
    * @param Class $clazz clase por la que se buscan los atributos
    * @return array
    */
   public function hasManyAttributesOfClass( $clazz )
   {
      $res = array();
      foreach ($this->hasMany as $attrname => $hmclazz) if ($clazz === $hmclazz) $res[] = $attrname;
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
      foreach ($this->hasOne as $attrname => $hmclazz) if ($clazz == $hmclazz) $res[] = $attrname;
      return $res;
   }

   // TODO hacer privada
   /**
    * Implementacion mejorada de attributesOfSameRelationship (removida).
    * Retorna true si:
    * - this tiene una relacion hasMany o hasOne con nombre assocRole y clase assocClass, y,
    * - assocClass tiene una relacion hasMany o hasOne con nombre backRole y clase $this->getClass
    */
   public function bidirRolesOfSameRel($assocRole, $assocClass, $backRole)
   {
      //echo "<h2>bidirRolesOfSameRel: ".$this->getClass()." $assocRole : $assocClass ($backRole)</h2>";
      //print_r($this->hasMany);
      //print_r($this->hasOne);

      // TODO: ver que el assocRole y el backRole vienen ya con el assocName codificado
      $assocRole = $this->getRoleWithAssocName($assocRole); // Nombre completo con assoc si lo tiene
      
      //echo "<h3>full role $assocRole</h3>";
      
      if (!($thisHasMany = array_key_exists($assocRole, $this->hasMany)) &&
          !($thisHasOne = array_key_exists($assocRole, $this->hasOne)))
      {
         throw new Exception('La clase '. $this->getClass() .' no tiene declarada una relacionas hasMany o hasOne con nombre '. $assocRole);
      }
      
      // FIXME: para luego pasarle una instancia en lugar de la clase assocClass
      $asocIns = new $assocClass();
      $backRole = $asocIns->getRoleWithAssocName($backRole);
      if (!($assocHasMany = array_key_exists($backRole, $asocIns->hasMany)) &&
          !($assocHasOne = array_key_exists($backRole, $asocIns->hasOne)))
      {
         throw new Exception('La clase '. $asocIns->getClass() .' no tiene declarada una relacionas hasMany o hasOne con nombre '. $backRole);
      }
      
      // Veo si los roles tienen o no el assocName codificado
      $pos1 = strrpos($assocRole, "__");
      $pos2 = strrpos($backRole, "__");
      
      if ($pos1 === false && $pos2 === false)
      {
         //echo "<h3>Ninguno tiene assocName</h3>";
        
         // Si no hay ninguna otra relacion declarada entre ambas clases, son atributos de la misma relacion.
         // En ese caso, existe una sola relacion declarada de cada lado y no tiene el nombre de la asociacion.
         
         if ($thisHasMany)
            $roles1 = $this->hasManyAttributesOfClass($assocClass);
         else // thisHasOne
            $roles1 = $this->hasOneAttributesOfClass($assocClass);
         
         if ($assocHasMany)
            $roles2 = $asocIns->hasManyAttributesOfClass($this->getClass());
         else // assocHasOne
            $roles2 = $asocIns->hasOneAttributesOfClass($this->getClass());
         
         // FIXME: si this hasMany assocClass y assocClass hasOne this, se lanza la excepcion de abajo y no deberia.
         // Ese tipo de relacion sigue siendo bidir pero es 1-*
         // Aca lo que verifica es *-*
         
         // FIXME:
         // false si no tengo assocName codificado pero tengo mas de una relacion declarada (en ese caso
         // deberia tirar una excepcion porque es un modelo invalido)
         if (count($roles1) == 1 && count($roles2) == 1) return true;
         
         throw new Exception("Se tiene mas de una relacion bidireccional declarada entre ".$this->getClass()." y $assocClass, sin haber declarado el nombre de la asociacion para establecer las correspondencias entre roles de la misma asociacion");
      }
      else if ($pos1 !== false && $pos2 !== false)
      {
         //echo "<h3>Ambos tienen assocName</h3>";
         // Ambos tienen la associacion declarada, veo si son las mismas
         
         // Si tengol role__assoc, me quedo solo con __assoc
         $suf1 = substr( $assocRole, $pos1 );
         $suf2 = substr( $backRole, $pos2 );
      
         return ( strcmp($suf1, $suf2) == 0 ); // si ambas assocs son iguales, true.
      }
      
      //echo "<h3>Uno si uno no: $pos1, $pos2</h3>";
      
      // Importante para completar la implementacion:
      // FIXME: una validacion que se deberia hacer del modelo es que si tienen mas de una relacion
      // (y por lo menos una bidir), se deberia tener el nombre de la relacion codificado para saber cual es la bidir y cual no.
      // Si un rol tiene la relacion declarada y el otro no, no son roles de la misma relacion.
      return false;
   }
   
   /**
    * Inversa a getRoleWithAssocName y reimplementacion de getAssocRoleName
    */
   private static function getRoleFromRoleAsoc( $roleAndAssoc )
   {
      $pos = strrpos($roleAndAssoc, "__");
      if ( $pos === false ) return $roleAndAssoc; // Si $roleAndAssoc es solo 'role', devuelve solo 'role'
      return substr( $roleAndAssoc, -$pos); // Si $roleAndAssoc es 'role__assoc' devuelve solo 'role'
   }
   
   /** Reescritura de la vieja getFullAttributename
    * 
    * Devuelve el rol y nombre de asociacion codificados en un string role__assocName si
    * el role esta declarado en la clase y si tiene el assocName declarado. Sino tiene el
    * assocName declarado, devuelve el mismo role. Si el role no esta declarado en la clase
    * como hasMany o hasOne, lanza una excepcion.
    */
   private function getRoleWithAssocName( $role )
   {
      // TEST
      //print_r($this->hasMany);
      if (empty($role)) throw new Exception("role no puede ser vacio");
      // debug_print_backtrace();
      //echo "<h1>getRoleWithAssocName '$role'</h1>";
      
      if (array_key_exists($role, $this->hasMany)) return $role; // el role no tiene assoc declarada.
      if (array_key_exists($role, $this->hasOne)) return $role; // el role no tiene assoc declarada.
      
      //if (array_key_exists($role, $this->hasMany)) // no se puede preguntar porque lo que hay en hasMany es role__assoc y quiero hacer exists por role solo, siempre dara false.
      //{
         foreach ($this->hasMany as $roleAndAssoc => $clazz) // roleAndAssoc puede ser solo 'role' o 'role__assocName'
         {
            $pos = stripos($roleAndAssoc, $role.'__'); // Si role es prefijo de roleAndAssoc (es necesario ponerle el __ sino va a dar que 'abc' es prefijo de 'abcd__assoc')
            if ($pos === 0)
            {
               return $roleAndAssoc;
            }
         }
         //return $role; // Esta en hasMany pero sin assocName
      //}
      //else if (array_key_exists($role, $this->hasOne)) // idem caso hasMany
      //{
         foreach ($this->hasOne as $roleAndAssoc => $clazz)
         {
            $pos = stripos($roleAndAssoc, $role.'__');
            if ($pos === 0) // veo si el nombre del atributo es prefijo del nombre real, me viene "role" y $attr es "role__assoc".
            {
               return $roleAndAssoc;
            }
         }
         //return $role;
         
         // Le agrego el mensaje del atributo simple porque es precondicion que para que se llame a este metodo el atributo sea HO o HM, pero por un error de tipeo en un getXXX, ej. XXY, en realidad se esta pidiendo un atributo simple y el error le dice que no es un atributo complejo lo que confunde.
         throw new Exception("El rol '$role' no esta declarado como atributo simple, hasMany o cono hasOne en la clase ". $this->getClass());
         
      //}
      //else throw new Exception("El rol $role no esta declarado como hasMany o cono hasOne en la clase ". $this->getClass());
      
   } // getRoleWithAssocName

   // Se invoca sobre el lado fuerte de una relacion n-n, pasandole como parametros la clase
   // del lado debil y el atributo correspondiente de esa clase cuando la relacion es bidireccional.
   // (si no es bidireccional no se deberia llamar a esta funcion).
   // El atributo assocAttribute de assocClass apunta a this->getClass() y quiero mi atributo que apunta a assocClass.
   public function getHasManyAttributeNameByAssocAttribute( $assocClass, $assocAttribute )
   {
      //echo "<h1 style='color:green;'>getHasManyAttributeNameByAssocAttribute: assocClass=$assocClass assocAttr=$assocAttribute</h1>";
      
      foreach ($this->hasMany as $attr => $class)
      {
         if ($class == $assocClass && $this->bidirRolesOfSameRel($attr, $assocClass, $assocAttribute)) return $attr;
      }
      
      //throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no existe el atributo hasMany en ". get_class($this) . " correspondiente al atributo " . $assocAttribute . " de " .$assocClass);
      return NULL; // tal vez con retornar NULL alcance... en lugar de exceptuar.
   }

   // Idem para hasOne.
   // El atributo assocAttribute de assocClass apunta a this->getClass() y quiero mi atributo que apunta a assocClass.
   public function getHasOneAttributeNameByAssocAttribute( $assocClass, $assocAttribute )
   {
      Logger::getInstance()->po_log("PO:getHasOneAttributeNameByAssocAttribute assocClass=$assocClass assocAttr=$assocAttribute");
      
      foreach ($this->hasOne as $attr => $class)
      {
         if ($class == $assocClass && $this->bidirRolesOfSameRel($attr, $assocClass, $assocAttribute)) return $attr;
      }
      
      //throw new Exception("PO.getHasManyAttributeNameByAssocAttribute: no existe el atributo hasMany en ". get_class($this) . " correspondiente al atributo " . $assocAttribute . " de " .$assocClass);
      return NULL; // tal vez con retornar NULL alcance... en lugar de exceptuar.
   }

   // Funcion inversa a belongsTo
   public function isOwnerOf( $attr )
   {
      // SOL TICKET #2
      // Dependiendo del tipo de relacion se si un objeto es duenio de otro:
      // - 1)  Si la relacion es A (1)->(1) B entonces se necesita belongsTo explicito para no salvar en cascada relaciones que en realidad son blandas (p.e. modelado de *->1 donde el lado * en realidad es blando). (desde el modelo, esto es igual a (*)->(1))
      // - 2)  Si la relacion es A (1)<->(1) B entonces se necesita belongsTo para saber cual es el lado fuerte.
      // - 3)  Si la relacion es A (1)->(*) B entonces B belongsTo A.
      // - 4)  Si la relacion es A (1)<->(*) B entonces B belongsTo A.
      // - 5)  Si la relacion es A (*)->(*) B entonces B belongsTo A. (desde el modelo, es lo mismo que (1)->(*))
      // - 6)  Si la relacion es A (*)<->(*) B entonces se necesita belongsTo en algun lado.
      //
      // La clase actual es A, el obj es de clase B.

      $_thisClass = get_class($this); //self::$thisClass; // get_class da PO, deberia usar otro valor y no la clase...

      // Verifico si tengo el atributo y esta en una relacion (hasMany o hasOne).
      //
      if (array_key_exists( $attr, $this->hasOne ))
      {
         $obj = new $this->hasOne[$attr]();

         // Si la relacion es unidireccional, se es duenio del otro solo si el otro declara belongsTo mi clase.
         if ($obj->hasOneOfThis( $_thisClass )) // 2) bidireccional 1..1
         {
            return $obj->belonsToClass( $_thisClass ); // Si el objeto que quiero saber si soy duenio pertenece a mi => si soy duenio de el.
         }
         else // 1) unidireccional 1..1
         {
            //return true;
            return $obj->belonsToClass( $_thisClass ); // Ahora se pide belongsTo obligatorio para 1..1 unidireccional (esto evita que se salven en cascada links que realmente son blandos)
         }
      }
      else if (array_key_exists( $attr, $this->hasMany ))
      {
         // Si tengo una relacion hasMany con migo mismo, tengo 1->* o *->*, para ambos casos debería devolver true.
         if ($this->hasMany[$attr] == $_thisClass) return true;
         
         $obj = new $this->hasMany[$attr]();

         if ($obj->hasOneOfThis( $_thisClass )) // 4) bidireccional 1..*
         {
            return true;
         }
         else
         {
            // 6) bidireccional *..*
            if ($obj->hasManyOfThis( $_thisClass  ))
            {
               return $obj->belonsToClass( $_thisClass ); // problema: get_class(this) tira PO...
            }
            else // casos 3 o 5, como es unidireccional, toma el control la clase del lado que no es visto de la otra.
            {
               return true;
            }
         }
      }

      // Si llega aca deberia tirar un warning xq el atributo que me pasaron no es de una relacion...
      return false;
      
   } // isOwnerOf

   // Simplemente se fija si tengo la clase en la lista de objetos a los que pertenezco.
   // Busqueda simple del valor pasado.
   public function belonsToClass( $className )
   {
      foreach ( $this->belongsTo as $belonsToClass )
      {
         if ( $belonsToClass == $className ) return true;
      }
      return false;
   }

   public function hasAttribute( $attr )
   {
      return array_key_exists( $attr, $this->attributeTypes ) || array_key_exists($attr, $this->hasOne) || array_key_exists($attr, $this->hasMany);
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

   public function aSet( $attribute, $value )
   {
      Logger::getInstance()->po_log("PO:aSet $attribute=". print_r($value, true));

      
      // Chekeo is_scalar para seteo de atributos simples.
      // Se agregaron returns para los casos de seteo correcto.
      // Chekeo de is_null para hasOne.
      // Consideracion de valor null para hasOne.

      // VERIFY: CUal es la joda de discutir en que lista esta si al final hago lo mismo ???
      // SIRVE PARA VERIFICAR QUE LO QUE ESTOY SETEANDO ES VALIDO.
      // CHECK 1: El atributo esta en la lista de atributos?
      if (isset($this->attributeTypes[$attribute]) || array_key_exists($attribute, $this->attributeTypes))
      {
	     // https://code.google.com/p/yupp/issues/detail?id=172
		 // Para DATES, SQLServer devuelve un DateTime object, por lo que no es escalar (integer, float, string, boolean) y tira excepcion.
		 // DateTime > PHP 5.2
		 if ( Datatypes::isDateTime($this->attributeTypes[$attribute]) && $value instanceof DateTime)
		 {
		    $value = $value->format('Y-m-d H:i:s'); // Saca el string de la fecha
		 }
	  
         // Si el valor es null o es un tipo simple (no una clase)
         //  - Dejo tambien setear NULL xq al setear email_id puede ser NULL 
         //    y un valor simple tambien puede ser NULL si se lo desea.
         if ($value !== NULL && !is_scalar($value))
         {
            throw new Exception( "El valor para el atributo simple $attribute no es simple, es un " . gettype($value) );
         }
         
         // TICKET: http://code.google.com/p/yupp/issues/detail?id=35
         // Resuelve el problema de que si es un booleano y carga de la base,
         // el tipo del valor pasa a ser string y debe mantener el tipo boolean de PHP.
         if ( $this->attributeTypes[$attribute] == Datatypes :: BOOLEAN )
         {
            if ( is_bool($value) ) $this->attributeValues[$attribute] = $value;
            else
            {
               // TODO: otro valor posible podria ser "true" o "false" como strings.
               // TODO: ademas depende del DBMS
               //  - "0"/"1" para MySQL funciona
               //  - "f"/"t" para Postgres funciona
               
               // TODO: implementar con in_array(needle, array)
               $boolFalseValues = array(0, "0", "f", "F", "false", "FALSE");
               $boolTrueValues = array(1, "1", "t", "T", "true", "TRUE");
               
               // Si esta en trueValues es true, si esta en falseValues es false, otro caso no es soportado.
               if (in_array($value, $boolTrueValues)) $this->attributeValues[$attribute] = true;
               else if (in_array($value, $boolFalseValues)) $this->attributeValues[$attribute] = false;
               else throw new Exception("El valor '$value' para '$attribute' no es un valor booleano valido"); // Si es otro valor, no es soportado
              
               //if ( $value === "0" || $value === 0 || $value === "f" ) $this->attributeValues[ $attribute ] = false;
               //else if ( $value === "1" || $value === 1 || $value === "t" ) $this->attributeValues[ $attribute ] = true;
            }
         }
         else
         {
            // TODO: verificar que el tipo del dato corresponde con el tipo del campo.
            $this->attributeValues[$attribute] = $value;
         }

         $this->dirty = true; // Marca como modificada
         return;
      }
      else // FIXME OPTIMIZACION: aqui deberia buscar por hasMany y hasOne, y recien cuando veo que no encuentro, hacer la busqueda por parecidos.
      {
         // FIXME: esto en que casos se ejecuta?
        
         // Pruebo si el attribute no es el nombre de la columna que
         // corresponde con algun atributo de esta clase en el ORM.
         foreach ( $this->attributeTypes as $classAttr => $type )
         {
            if (DatabaseNormalization::col($classAttr) == $attribute)
            {
               if ($value !== NULL && !is_scalar($value))
               {
                  throw new Exception( "El valor para el atributo simple $attribute no es simple, es un " . gettype($value) );
               }
               
               $this->attributeValues[$classAttr] = $value;
               $this->dirty = true; // Marca como modificada
               return;
            }
         }
      }
      
      // ======================================================================
      // Es hasMany o hasOne
      
      // si no esta en la lista de atributos, me fijo si no encuentro un atributo con
      // nombre "similar" a $attribute, esto pasa porque si el atributo es normalizedName
      // en la tabla guarda 'normalizedname' todo en minusculas (por YuppConventions).
      // Se debe hacer idem para hasOne y hasMany
      
      // Si el rol tiene el nombre de la assoc declarado, necesito ver cual es el nombre
      // completo de la key en hasOne o hasMany porque usa attribute__assocName.
      $attribute = $this->getRoleWithAssocName( $attribute );

      if (isset($this->hasOne[$attribute]) || array_key_exists($attribute, $this->hasOne))
      {
         if ($value !== NULL && !is_subclass_of( $value, 'PersistentObject' ) ) // El caso null es valido pero falla en el is_subclass_of, por eso se agrega como OR a la condicion.
         {
            throw new Exception( "El valor para el atributo hasOne $full_attribute no es persistente, es un " . gettype($value) );
         }
         
         $this->attributeValues[$attribute] = $value; // email

         // Si seteo NULL no puedo preguntarle el id!!!
         $refAttrName = DatabaseNormalization::simpleAssoc($attribute); // "email_id"
         if ( $value ) $this->attributeValues[$refAttrName] = $value->getId(); // Seteo tambien "email_id", puede ser NULL !!!
         else $this->attributeValues[$refAttrName] = NULL; // Seteo tambien "email_id", puede ser NULL !!!

         $this->dirtyOne = true; // Marca como modificada
         return;
      }
      else
      {
         // Pruebo si el attribute no es el nombre de la columna que
         // corresponde con algun atributo de esta clase en el ORM.
         foreach ($this->hasOne as $classHOAttr) // FIXME: ver estructura
         {
            if (DatabaseNormalization::col($classHOAttr) == $attribute)
            {
               if ($value !== NULL && !is_subclass_of($value, 'PersistentObject')) // El caso null es valido pero falla en el is_subclass_of, por eso se agrega como OR a la condicion.
               {
                  throw new Exception( "El valor para el atributo hasOne $full_attribute no es persistente, es un " . gettype($value) );
               }
               
               $this->attributeValues[$attribute] = $value; // email
                  
               // Si seteo NULL no puedo preguntarle el id!!!
               $refAttrName = DatabaseNormalization::simpleAssoc($attribute); // "email_id"
               if ( $value ) $this->attributeValues[$refAttrName] = $value->getId(); // Seteo tambien "email_id", puede ser NULL !!!
               else $this->attributeValues[$refAttrName] = NULL; // Seteo tambien "email_id", puede ser NULL !!!

               $this->dirtyOne = true; // Marca como modificada
               return;
            }
         }
      }

      if ( array_key_exists($attribute, $this->hasMany) ) // El valor deberia ser una lista de objetos.
      {
         // TODO: ademas deberia ser de objetos persistentes.
         // TODO: NULL es un valor valido para una lista de objetos ?
         if ( !is_array($value) ) // $value Debe ser un array porque hago set de un hasMany
         {
            throw new Exception("El valor para el atributo ". $attribute ." debe ser un array.");
         }
         
         $this->attributeValues[$attribute] = $value;
         $this->dirtyMany = true; // Marca como modificada
         return;
      }
      else
      {
         // Pruebo si el attribute no es el nombre de la columna
         // que corresponde con algun atributo de esta clase en el ORM.
         foreach ( $this->hasMany as $classHMAttr )// FIXME: ver estructura
         {
            if ( DatabaseNormalization::col($classHMAttr) == $attribute )
            {
               if ( !is_array($value) )
               {
                  throw new Exception("El valor para el atributo ". $attribute ." debe ser un array.");
               }
               
               $this->attributeValues[$attribute] = $value;
                  
               // Si seteo NULL no puedo preguntarle el id!!!
               $refAttrName = DatabaseNormalization::simpleAssoc($attribute); // "email_id"
               if ( $value ) $this->attributeValues[$refAttrName] = $value->getId(); // Seteo tambien "email_id", puede ser NULL !!!
               else $this->attributeValues[$refAttrName] = NULL; // Seteo tambien "email_id", puede ser NULL !!!
               
               $this->dirtyMany = true; // Marca como modificada
               return;
            }
         }
      }

      throw new Exception("PO.aSet: El atributo '$attribute' no existe en la clase (". get_class($this) .")");

   } // aSet


   // TODO: Al setear un objeto de hasOne deberia setear el "email_id" (que podria ser null o un entero)...
   // Devuelve valores de atributos, mas estructurado que OO, pero es para uso interno desde DAL por ejemplo.
   public function aGet( $attr ) // Cambie el nombre de get xq se choca con el get que quiero poner de wrapper del PM.
   {
      //Logger::getInstance()->po_log("PO:aGet $attr");

      // Si no es un atributo simple tengo que ver si hago lazy load...
      if ( !array_key_exists($attr, $this->attributeTypes) )
      {
         // Si llega aqui estoy seguro de que no pide un atributo simple, se pide uno complejo.
         // Podria ser simple pero se paso un nombre normalizado para una columna.

         // Si el rol tiene el nombre de la assoc declarado, necesito ver cual es el nombre
         // completo de la key en hasOne o hasMany porque usa attribute__assocName.
         $attr = $this->getRoleWithAssocName( $attr );

         // Soporte para lazy loading para hasOne y hasMany
         // No verifico que tenga valor porque deberia venir inicializado
         //if ( isset($this->attributeValues[$attr]) && $this->attributeValues[$attr] === self::NOT_LOADED_ASSOC )
         if ($this->attributeValues[$attr] === self::NOT_LOADED_ASSOC)
         {
            // Si no tiene ID todavia no se guardo, entonces no puede cargar lazy algo que no se ha guardado.
            if (!isset($this->attributeValues['id']))
            {
               return NULL;
            }

            $pm = PersistentManager::getInstance();

            if ( array_key_exists($attr, $this->hasMany) )
            {
               $pm->get_many_assoc_lazy($this, $attr); // El atributo se carga, no tengo que setearlo...

               // Se marca el dirtyMany al pedir hasMany porque no se tiene control
               // sobre como se van a modificar las instancias de la relacion solicitadas,
               // si dirtyMany esta en false y las intancias son modificadas, al salvar esta
               // intancia, las hasMany no se van a salvar en cascada.
               $this->dirtyMany = true;
            }
            else if ( array_key_exists($attr, $this->hasOne) )
            {
               // Si hay id de asociacion, lo cargo, si no lo pongo en NULL
               $assocAttr = DatabaseNormalization::simpleAssoc( $attr ); // email_id
               $assocId = $this->attributeValues[ $assocAttr ];
               if ( $assocId != NULL )
               {
                  $this->attributeValues[ $attr ] = $pm->get_object( $this->hasOne[$attr], $assocId );

                  // Se marca el dirtyOne al pedir hasOne porque no se tiene control sobre como se va a modificar la instancia solicitada.
                  $this->dirtyOne = true;
               }
               else $this->attributeValues[ $attr ] = NULL;
            }
            else // Busca por similares
            {
               // Aun puede ser simple porque se pide por el nombre de la columna en lugar del nombre del atributo,
               // entonces primero hay que buscar si no se pide por el nombre de la columna. Idem a lo que hago en aSet.
               foreach ( $this->attributeTypes as $classAttr => $type )
               {
                  if ( DatabaseNormalization::col($classAttr) == $attr ) // Busca con normalizacion
                  {
                     if (isset($this->attributeValues[ $classAttr ])) // Encuentra con normalizacion
                     {
                        return $this->attributeValues[ $classAttr ];
                     }
                     else return NULL;
                  }
               }

               throw new Exception("El atributo ". $attr ." no existe en la clase (" . get_class($this) . ")");
            }
         } // si no esta cargada
      } // si no es simple

      // Devuelve atributo hasOne o hasMany (la devolucion de atributos simples se hace arriba).
      // Si el hasOne o hasMany no estaban cargados, fueron cargados bajo demanda y devueltos aqui.
      if (isset($this->attributeValues[$attr]))
      {
         return $this->attributeValues[$attr];
      }

      return NULL;

   } // aGet

   public function aContains( $attribute, $value )
   {
      Logger::getInstance()->po_log("PO:aContains $attribute=". print_r($value, true));
      
      
      // Si el rol tiene el nombre de la assoc declarado, necesito ver cual es el nombre
      // completo de la key en hasOne o hasMany porque usa attribute__assocName.
      $attribute = $this->getRoleWithAssocName( $attribute );
      
      // CHEK: Attribute es un atributo hasMany.
      if (!isset($this->hasMany[$attribute]) && !array_key_exists($attribute, $this->hasMany))
      {
         throw new Exception("El atributo hasMany $attribute no existe en la clase (" . get_class($this) . ")");
      }
      
      $this->lazyLoadHasMany($attribute);


      // Value puede ser: entero (entonces es un id), PO (entonces se compara por su id), Clausura (se hace una busqueda).
      $id = -1;
      if ( is_numeric($value) ) //is_int($value) ) // Busca por id // habia problema al pasarle un id entero pero como string...
      {
         $id = $value;
      }
      else if ( is_subclass_of($value, 'PersistentObject') ) // Busca por id del PO
      {
         $id = $value->getId(); // FIXME: debe tener id seteado!
      }

      if ( $id != -1 )
      {
         // FIXME: El atributo deberia ser un array (capaz puede ser null, tengo que fijarme bien)
         if ($this->attributeValues[$attribute])
         {
            foreach( $this->attributeValues[$attribute] as $assocObj )
            {
               if ($assocObj->getId() == $id) return true;
            }
         }
         return false; // no lo encuentra.
      }

      // TODO: else ...
      // TODO: por clausura
      throw new Exception("Tipo de busqueda no soportada, value debe ser un entero o un PersistentObject y su valor es " . print_r($value,true));
   }

   /**
    * Agrega una instancia de PO a la coleccion de una relacion hasMany.
    * La operacion se hace en memoria, no guarda en la base de datos.
    */
   public function aAddTo($attribute, PersistentObject $value)
   {
      Logger::getInstance()->po_log("PO:aAddTo $attribute []=".$value->getClass());
      
      // CHEK: attribute es un atributo hasMany
      
      // Si el rol tiene el nombre de la assoc declarado, necesito ver cual es el nombre
      // completo de la key en hasOne o hasMany porque usa attribute__assocName.
      $attribute = $this->getRoleWithAssocName( $attribute );


      // TODO: Se podria poner la restriccion de que no se puede hacer set('id', xxx); 
      // o sea el id no se puede modificar por el usuario.
      // (asi puedo asumir que si no tiene id es xq no esta guardado... y me ahorro consultar si existe en la base)

      // Aqui se hace todo lo del codigo comentado abajo
      $this->lazyLoadHasMany($attribute);


      // Chekeo de tipos con el tipo definido en hasMany para este atributo.
         
      // Si es colection, se agrega normalmente, 
      // si es set se verifica que no hay otro con el mismo id, 
      // si es list al salvar y cargar se respeta el orden en el que se agregaron los elementos.
         
      $add = false;
         
      switch ( $this->hasManyType[$attribute] )
      {
         case self::HASMANY_COLLECTION:
         case self::HASMANY_LIST: // Por ahora hace lo mismo que COLECTION, en PM se verificaria el orden.
            
            $add = true;
               
         break;
         case self::HASMANY_SET: // Buscar repetidos por id, si ya esta no agrego de nuevo.
            
            $found = false;
            reset( $this->attributeValues[$attribute] );
            $elem = current( $this->attributeValues[$attribute] );
            while ( $elem )
            {
               if ($elem->getId() === $value->getId() )
               {
                  $found = true;
                  break; // while
               }
               $elem = next( $this->attributeValues[$attribute] );
            }

            $add = !$found; // Agrega solo si no esta.

         break;
      }

      if ($add)
      {
         $this->attributeValues[$attribute][] = $value; // TODO: Verificar que args0 es un PersistentObject y es simple!
                                                        // FIXME: bool is_subclass_of ( mixed $object, string $class_name )
         $this->dirtyMany = true; // Marca como editado el hasMany
      }
   } // aAddTo

   private function lazyLoadHasMany($attr)
   {
      if ( !array_key_exists($attr, $this->hasMany) )
      {
         throw new Exception("El atributo ". $attr ." no es un atributo hasMany en la clase (" . get_class($this) . ")");
      }
      
      // Sino esta cargado
      if ( $this->attributeValues[$attr] == self::NOT_LOADED_ASSOC )
      {
         // Si el objeto esta guardado, trae las clases ya asociadas, si no, inicializa el vector.
         if ( $this->getId() != NULL ) // && self::$pm->exists( get_class($this), $this->getId() ) ) No necesito hacer el exists porque garantizo que si tiene id esta guardado.
         {
            PersistentManager::getInstance()->get_many_assoc_lazy( $this, $attr ); // Carga elementos de la coleccion... si es que los hay... y si no inicializa con un array.
         }
         else // Si no esta salvado...
         {
            $this->attributeValues[$attr] = array(); // Inicializa el array...
         }
      }
   }

   /**
    * @pre si $value es un PersistentObject, debe tener el id seteado.
    */
   public function aRemoveFrom ($attribute, $value, $logical = false)
   {
      // CHEK: attribute es un atributo hasMany
      // CHEK: value es un PO
      
      // Si el rol tiene el nombre de la assoc declarado, necesito ver cual es el nombre
      // completo de la key en hasOne o hasMany porque usa attribute__assocName.
      $attribute = $this->getRoleWithAssocName( $attribute );
      
      // Aqui se hace todo lo del codigo comentado abajo
      $this->lazyLoadHasMany($attribute);

      // Aqui llega con la coleccion cargada o inicializada, siempre!
      // =================================================================

      // Si la coleccion no tiene elementos no hace nada.
      if ( count($this->attributeValues[$attribute]) == 0 ) return;
     
      // Aqui llega si hay elementos en la coleccion.
      // =================================================================

      // Idem a *Contains
      $id = -1;
      if (is_int($value)) // Busca por id
      {
         $id = $value;
      }
      else if (is_subclass_of($value, 'PersistentObject')) // Busca por id del PO
      {
         $id = $value->getId(); // TODO CHECK: debe tener id seteado!
         if ($id === NULL)
         {
            throw new Exception("El objeto que se desea remover debe tener el id seteado y tiene id vacio.");
         }
      }

      if ($id != -1)
      {
         // Busco en atributos hasMany attr y si encuentro elimino.
         foreach ($this->attributeValues[$attribute] as $i => $obj)
         {
            if ($obj->getId() == $id) // Busco por id.
            {
               // Saca de la relacion el objeto con id=$id
               $this->attributeValues[$attribute][$i] = null;
               $this->attributeValues[$attribute] = array_filter($this->attributeValues[$attribute]); // La forma PHP de hacerlo... array sin NULLs...
    
               // TODO: Verificar si el nombre de este atributo es el correcto!
               // Dado el otro objeto y mi atributo, quiero el atributo del otro objeto que corresponda a la relacion con mi atributo.
               $attr2 = $obj->getHasOneAttributeNameByAssocAttribute( get_class($this), $attribute );
               if ($attr2 == NULL) $attr2 = $obj->getHasManyAttributeNameByAssocAttribute( get_class($this), $attribute );
               // FIXME: Problema si el atributo es hasOne! no encuentra el nombre del atributo!
               // TODO: La operacion deberia ser para los 2 lados y ser tanto para n-n como para 1-n
    
               // FIXME: Si la relacion es 1<->* deberia setear en NULL el lado 1 (ya lo mencione en otro lugar...) y salvar ese objeto.
    
               // Por defecto la asociacion se borra fisicamente.
               PersistentManager::getInstance()->remove_assoc( $this, $obj, $attribute, $attr2, $logical ); // TODO: Ok ahora falta hacer que el get considere asociaciones solo con daleted false cuando carga.

               $this->dirtyMany = true; // Marca como editado el hasMany
    
               return;
            } // Si el elmento esta en la coleccion
         }
      } // Necesito el id porque se usa para matchear
   } // aRemoveFrom


   /**
    * Similar a aRemoveFrom, pero quita todos los elementos del atributo hasMany.
    */
   public function aRemoveAllFrom ($attribute, $logical = false)
   {
      // CHEK: attribute es un atributo hasMany

      // Si el rol tiene el nombre de la assoc declarado, necesito ver cual es el nombre
      // completo de la key en hasOne o hasMany porque usa attribute__assocName.
      $attribute = $this->getRoleWithAssocName( $attribute );

      // Verifica si la relacion hasMany esta cargada, y sino la carga
      $this->lazyLoadHasMany($attribute);

      // Aqui llega con la coleccion cargada o inicializada, siempre!
      // =================================================================

      // Si la coleccion no tiene elementos no hace nada.
      if ( count($this->attributeValues[$attribute]) == 0 ) return;
     
      // Aqui llega si hay elementos en la coleccion.
      // =================================================================

      $pm = PersistentManager::getInstance();

      // Busco en atributos hasMany attr y si encuentro elimino.
      foreach ( $this->attributeValues[$attribute] as $i => $obj )
      {
         // Saca de la relacion el objeto con id=$id
         $this->attributeValues[$attribute][$i] = null;
         $this->attributeValues[$attribute] = array_filter($this->attributeValues[$attribute]); // La forma PHP de hacerlo... array sin NULLs...

         // TODO: Verificar si el nombre de este atributo es el correcto!
         // Dado el otro objeto y mi atributo, quiero el atributo del otro objeto que corresponda a la relacion con mi atributo.
         $attr2 = $obj->getHasOneAttributeNameByAssocAttribute( get_class($this), $attribute );
         if ($attr2 == NULL) $attr2 = $obj->getHasManyAttributeNameByAssocAttribute( get_class($this), $attribute );
         // FIXME: Problema si el atributo es hasOne! no encuentra el nombre del atributo!
         // TODO: La operacion deberia ser para los 2 lados y ser tanto para n-n como para 1-n
         
         // Por defecto la asociacion se borra fisicamente.
         $pm->remove_assoc( $this, $obj, $attribute, $attr2, $logical ); // TODO: Ok ahora falta hacer que el get considere asociaciones solo con daleted false cuando carga.
      }
      
      $this->dirtyMany = true; // Marca como editado el hasMany
      
   } // aRemoveAllFrom

   /**
    * Elimina un elemento de la base de datos, eliminacion fisica por defecto.
    * @param boolean $logical indica si la eliminacion es logica (true) o fisica (false).
    * @todo: hacer delete por clase/id, esta es solo por instancia.
    */
   public function delete($logical = false)
   {
      Logger::getInstance()->po_log("delete $logical");
      
      // FIXME: devolver algo que indique si se pudo o no eliminar.
      // FIXME: si no esta salvado (no tiene id), no se puede hacer delete.

      PersistentManager::getInstance()->delete( $this, $this->getId(), $logical ); // FIXME: no necesita pasarle el id, el objeto ya lo tiene...
      
      // http://code.google.com/p/yupp/issues/detail?id=127
      if ($logical) $this->setDeleted(true);
   }
   
   // Operadores sobre POs como conjuntos de atributos
   
   /**
    * Devuelve un PO con los atributos de $po1 que no estan en $po2.
    * El resultado es un
    */
   public static function less($po1, $po2)
   {
      // FIXME: faltan atributos hasOne y hasMany!!! tambien contraints xq afectan la generacion del esquema!!!
      $class = $po1->getClass();
      $res   = new $class(); // si hago una instancia de esta clase estoy en la misma, genera los atributos de la superclase...
      $hone  = $po1->getHasOne();
      $hmany = $po1->getHasMany();

      foreach($po1->getAttributeTypes() as $name => $type)
      {
         // Si el atributo es inyectado no lo saco!
         if ($po2->hasAttribute($name) && !$po2->isInyectedAttribute($name)) $res->removeAttribute($name); // le saco al po1 los atributos de po2 si es que los tiene...
         
         // Como po1 tiene un merge de los atributos de las subclases que se mapean en la misma tabla que po1, 
         // tengo que agregar los atributos que faltan en la instancia res pero estan en po1.
         // Y tengo que ver que no este en po2 xq si no le estoy metiendo el atributo que quiero eliminar...
         if (!$res->hasAttribute($name) && !$po2->hasAttribute($name)) $res->addAttribute($name, $type);
      }

      return $res;
   }
} // PersistenObject

/**
 * Modela la relacion 1..* con una tabla intermedia.
 */
class ObjectReference extends PersistentObject {

   // Valores posibles para el tipo. Atributos no persistentes!!!
   // Indican la direccion de la relacion (sirve para hacer determinista el tener relaciones *-* asi se en que sentido se puede recorrer la asociacion).
   const TYPE_ONEDIR = 1; // Solo el fuerte al debil.
   const TYPE_BIDIR  = 2; // Fuerte a debil y debil a fuerte.

   public function __construct( $args = array() )
   {
      $this->attributeTypes  = array(
        "owner_id" => Datatypes::INT_NUMBER,
        "ref_id"   => Datatypes::INT_NUMBER,
        "type"     => Datatypes::INT_NUMBER,
        "ord"      => Datatypes::INT_NUMBER
      );

      // Aca se pueden cargar valores por defecto!
      $this->attributeValues = array(
        "owner_id" => NULL,
        "ref_id"   => NULL,
        "type"     => NULL,
        "ord"      => NULL
      );

      $this->constraints = array(
        "owner_id" => array( Constraint::nullable(false) ),
        "ref_id"   => array( Constraint::nullable(false) ),
        "ord"      => array( Constraint::nullable(true) ) // Si el atributo hasMany no es LIST, aca se guarda NULL.
      );

      parent::__construct( $args );
   }
}

?>