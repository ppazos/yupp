<?php
/**
 * Este archivo contiene la definicion de la clase que maneja toda la logica de persistencia en alto nivel.
 * La cual se encarga de comunicarse con las capas de persistencia inferiores (DAL) y generar objetos persistentes con los datos cargados.
 * 
 * Created on 15/12/2007
 * 
 * @name core.persistent.PersistentManager.class.php
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @version v0.9.0
 * @package core.persistent
 * 
 */
YuppLoader::load('core.db.criteria2', 'Condition');
YuppLoader::load('core.db.criteria2', 'ComplexCondition');
YuppLoader::load('core.db.criteria2', 'CompareCondition');
YuppLoader::load('core.db.criteria2', 'BinaryInfixCondition');
YuppLoader::load('core.db.criteria2', 'UnaryPrefixCondition');
YuppLoader::load('core.utils',        'Callback');
YuppLoader::load('core.persistent',   'ArtifactHolder');
YuppLoader::load('core.persistent',   'MultipleTableInheritanceSupport');

/**
 * Esta clase implementa toda la logica necesaria para persistir objetos persistentes y 
 * para obtener datos de la base y crear objetos persistentes.
 * @package core.persistent
 * @subpackage classes
 */
class PersistentManager {

   private $po_loader; // POLoader Interface instance
   private $dal;
   
   const CASCADE_LOAD_ESTRATEGY = 1;
   const LAZY_LOAD_ESTRATEGY    = 2;
   
   private static $instance = NULL; // prueba con singleton normal

   public static function getInstance( $load_estragegy = NULL )
   {
      Logger::getInstance()->pm_log("PM::getInstance");
      
      if (!self::$instance)
      {
         // Definicion de estrategia de carga. Por defecto es Lazy.
         $po_loader = NULL;
         switch ($load_estragegy)
         {
            case self::LAZY_LOAD_ESTRATEGY:
               YuppLoader::load( "core.persistent", "LazyLoadStrategy" );
               $po_loader = new LazyLoadStrategy();
            break;
            case self::CASCADE_LOAD_ESTRATEGY:
               YuppLoader::load( "core.persistent", "CascadeLoadStrategy" );
               $po_loader = new CascadeLoadStrategy();
            break;
            default:
               YuppLoader::load( "core.persistent", "LazyLoadStrategy" );
               $po_loader = new LazyLoadStrategy();
            break;
         }
         // /Definicion de estrategia de carga.

         self::$instance = new PersistentManager( $po_loader );
      }
      
      return self::$instance;
   }

   private function __construct( $po_loader )
   {
      $po_loader->setManager( $this ); // Inversion Of Control
      $this->po_loader = $po_loader; // Siempre viene una estrategia, getInstance se encarga de eso.
      
      $ctx = YuppContext::getInstance();
      $appName = $ctx->getApp();
      if ($ctx->isAnotherApp()) $appName = $ctx->getRealApp();
      
//      Logger::getInstance()->on();
//      Logger::struct($ctx);
//      Logger::getInstance()->pm_log("PM::__construct appName: " . $appName);
//      Logger::getInstance()->pm_log("PM::__construct ctx.realApp: " . $ctx->getRealApp());
//      Logger::getInstance()->off();
      
      $this->dal = new DAL($appName); // FIXME: de donde saco el nombre de la app actual???
   }
   
   public function getDAL()
   {
      return $this->dal;
   }
   
   /**
    * Transaccionalidad.
    */
   public function withTransaction()
   {
      Logger::getInstance()->pm_log("PM::withTransaction");
      $this->dal->withTransaction();
   }
   
   public function commitTransaction()
   {
      Logger::getInstance()->pm_log("PM::commitTransaction");
      $this->dal->commitTransaction();
   }
   
   public function rollbackTransaction()
   {
      Logger::getInstance()->pm_log("PM::rollbackTransaction");
      $this->dal->rollbackTransaction();
   }

   /**
    * Se llama para los elementos asociados por hasMany. (independientemente que la relacion sea * o 1 del otro lado)
    * ownerAttr es el atributo de owner que apunta a child.
    * 
    * @param PersistenObject $owner objeto donde se declara la relacion con child, es el lado fuerte de la relacion.
    * @param PersistenObject $child objeto relacionado a owner, es el lado debil de la relacion.
    * @param string $ownerAttr nombre del atributo de owner que mantiene la relacion con child.
    * @param integer ord es el orden de child en el atributo hasMany ownerAttr de owner.
    */
   public function save_assoc( PersistentObject $owner, PersistentObject $child, $ownerAttr, $ord )
   {
//Logger::getInstance()->on();
      Logger::getInstance()->pm_log("PM::save_assoc " . get_class($owner) . " -> " . get_class($child));

      // Determinar si la relacion es unidireccional o bidireccional
      // =======================================================================

      // Todavia no se si la relacion es bidireccional.
      $relType = ObjectReference::TYPE_ONEDIR;

      // Se que el owner hasMany child, pero no se como es la relacion desde child,
      // puede no haber    => owner ->(*)child y la relacion es de tipo 1
      // puede ser hasOne  => owner (1)<->(*)child tengo que ver si tengo linkeado owner en child, si lo tengo, es de tipo 2.
      // puede ser hasMany => owner (*)<->(*)child, con owner la parte fuerte, tengo que fijarme si child contains al owner, si es asi, es de tipo 2.
      
      // FIXME: si child tiene 2 relaciones con owner, por ejemplo una hasOne y otra hasMany,
      // y el owner tiene tambien relaciones con child, si se le pide a child si tiene un
      // hasOne con owner y se esta llamando con ownerAttr que es el rol para la otra asociacion
      // va a retornar el nombre del atributo hasOne de la relacion hasOne que pertenece al otro rol no al ownerAttr.
      
      // Si es null no tengo un hasOne bidireccional desde el child, entonces es onedir
      $hoBidirChildAttr = $child->getHasOneAttributeNameByAssocAttribute( get_class($owner), $ownerAttr );
      
      if ( $hoBidirChildAttr != NULL) // hasOne
      {
         // Si es una relacion bidireccional hasOne desde el child
         
         //Logger::getInstance()->pm_log("PM::save_assoc hoBidirChildAttr=$hoBidirChildAttr no es null");
         
         $assocObj = $child->aGet($hoBidirChildAttr);

         // Si hay objeto, si esta cargado, y si coincide el id.
         if ($assocObj != NULL &&
             $assocObj !== PersistentObject::NOT_LOADED_ASSOC &&
             $assocObj->getId() === $owner->getId() ) // Verifica que el objeto owner es el que el objeto child tiene asociado en su rol $hoBidirChildAttr.
         {
            $relType = ObjectReference::TYPE_BIDIR;
         }
      }
      else // si el atributo no era de hasOne, es hasMany
      {
         //Logger::getInstance()->pm_log("PM::save_assoc hoBidirChildAttr es null");
         
         $hmBidirChildAttr = $child->getHasManyAttributeNameByAssocAttribute( get_class($owner), $ownerAttr );
         
         
         // Si hmBidirChildAttr es null, es porque no tengo bidireccionalidad
         if ($hmBidirChildAttr != NULL)
         {
            // Si es una relacion bidireccional hasMany desde el child
            //Logger::getInstance()->pm_log("PM::save_assoc hmBidirChildAttr $hmBidirChildAttr");
         
            // TEST
            //if ($child->aContains( $hmBidirChildAttr, $owner->getId() ))
            //   Logger::getInstance()->pm_log("PM::save_assoc child contains $hmBidirChildAttr ". $owner->getId());
             
            if ($hmBidirChildAttr != NULL && $child->aContains( $hmBidirChildAttr, $owner->getId() )) // FIXME: No se como se llama el atributo como para preguntar si child tiene a owner...
            {
               //Logger::getInstance()->pm_log("PM::save_assoc hmBidirChildAttr no es null");
                
               $relType = ObjectReference::TYPE_BIDIR;
            }
            //else
            //   Logger::getInstance()->pm_log("PM::save_assoc hmBidirChildAttr no es null");
         }
      }
      
      // FIN: Determinar si la relacion es unidireccional o bidireccional
      // =======================================================================
      
      
      // FIXME: si es hasOne, ¿esta bien que ejecute el codigo de abajo checkeando hasMany?
      //        Capaz es porque es bidireccional 1-* y lo esta mirando desde el otro lado de la relacion.

      // FIXME: (owner_id, ref_id) debe ser clave, o sea, unique porque primary key es "id". 
      // (en varios lugares como aca abajo y en remove_assoc considero que la relacion entre 2 objetos es unica en la misma tabla.)
      
      // No importa si el id es de la clase declarada en la relacion hasMany
      // o el id de la clase concreta, ahora son todos iguales.
      $ref_id = $child->getId();
      
      // El owner id debe ser el de la clase donde se declara la relacion hasmany
      // De todas formas, los ids de todas las instancias parciales de la clase declarada van a ser los mismos
      $owner_id = $owner->getId();
      
      // ========================================================================
      // VERIFICA DE QUE LA RELACION NO EXISTE YA.
      // FIXME: ojo ahora tendria que tener en cuenta la direccion tambien!

      //Logger::getInstance()->pm_log("PM: owner_id=$owner_id, ref_id=$ref_id " . __LINE__);
      // se pasan instancias... para poder pedir el withtable q se setea en tiempo de ejecucion!!!!
      $tableName = YuppConventions::relTableName( $owner, $ownerAttr, $child );
      $params['where'] = Condition::_AND()
                           ->add( Condition::EQ($tableName, "owner_id", $owner_id ) )
                           ->add( Condition::EQ($tableName, "ref_id",   $ref_id) );

      // FIXME: llamar a exists de DAL
      if ( $this->dal->count($tableName, $params) == 0 )
      {
         //Logger::getInstance()->pm_log("PM::save_assoc No existe la relacion en la tabla intermedia, hago insert en ella. " . __LINE__);
         
         // La asociacion se guarda con insert xq chekea q la relacion no exista para meterlo en la base.
         // TODO: deberia fijarme si los objetos con estos ids ya estan.
         // TODO2: Ademas deberia mantener las relaciones, si se eliminan objetos deberia borrar las relaciones!!!

         $refObj = NULL;
         if ( $owner->getHasManyType($ownerAttr) === PersistentObject::HASMANY_LIST )
         {
            $refObj = new ObjectReference(array("owner_id"=>$owner_id, "ref_id"=>$ref_id, "type"=>$relType, "ord"=>$ord));
         }
         else
         {
            $refObj = new ObjectReference(array("owner_id"=>$owner_id, "ref_id"=>$ref_id, "type"=>$relType));
         }

         $this->dal->insert( $tableName, $refObj );
      }
      
//Logger::getInstance()->off();
      
   } // save_assoc

   /**
    * Salva solo un objeto (sin las asociaciones)
    */
   public function save_object( PersistentObject $obj, $sessId )
   {
      Logger::getInstance()->pm_log("PM:save_object ". get_class($obj) );

      $tableName = YuppConventions::tableName( $obj );

      if ( !$obj->getId() ) // || !$dal->exists( $tableName, $obj->getId() ) ) // Si no tiene id, hago insert, si no update.
      {
         // FIXME: PO no se le deberia pasar a DAL, deberia transformarse a datos aqui.
         $this->dal->insert( $tableName, $obj ); // Salva los objetos, con sus datos simples.
      }
      else
      {
         // Nuevo: si se modificaron campos simples o asociaciones hasone hago udate, si no, no.
         if ($obj->isDirty() || $obj->isDirtyOne())
         {
            // El primero es siempre el que corresponde con la superclase de nivel 1
            $pinss = MultipleTableInheritanceSupport::getPartialInstancesToSave( $obj ); 
            foreach ( $pinss as $partialInstance )
            {
               $tableName = YuppConventions::tableName( $partialInstance );
       
               // ========================================================================================
               // Con el nuevo esquema de identificacion, el id del objeto es el mismo que el de todos
               // los objetos parciales de MTI, por lo que no es necesario hacer esto de pedir los ids.
               // Igualmente, como es update, tampoco lo veo necesario, porque la instancia parcial ya
               // tendria el identificador del padre, para que setearselo de nuevo? y tambienm, para que
               // setearle la class de nuevo si ya la tiene?

               // El id de todas las instancias parciales es el mismo.
               $id = $obj->getId();
                
               $partialInstance->setId( $id );
               $partialInstance->setClass( $obj->getClass() ); // En ambos casos tengo que colocar la clase correcta porque getPartialInstancesToSave me devuelve solo las clases que generan tabla... y si tengo C1 me va a devolver C, y la clase se la tengo que setear en C1 aunque se mapee en la misma tabla.
                
               //Logger::struct( $partialInstance, "PARTIAL INSTANCE" );
               //Logger::struct( $this->getDataFromObject($partialInstance), "PARTIAL INSTANCE" );
          
               // 2: Si existe, hace update
               if ( $this->dal->exists( $tableName, $id ) ) // VERIFY: este chekeo se hace en save del PM...
               {
                  $this->dal->update( $tableName, $this->getDataFromObject($partialInstance) );
               }
               else
               {
                  Logger::getInstance()->dal_log("DAL::update NO EXISTE " . $tableName . " " . $id . " " . __LINE__);
               }
            } // foreach ( $pinss as $partialInstance )
         } // si esta dirty
      }

      $obj->setSessId( $sessId );
      
   } // save_object


   /**
    * Si no esta salvado:
    *   Para cada hasOne:
    *     ...
    *   save_object()
    *   Para cada hasMany:
    *     ...
    * 
    * @return boolean true si no hubo error, false en caso contrario
    */
   public function save_cascade( PersistentObject $obj, $sessId )
   {
      Logger::getInstance()->pm_log("PM::save_cascade " . get_class($obj) . " SESSIONID: " . $sessId );

      // Para detectar loops en el salvado del modelo
      $obj->setLoopDetectorSessId( $sessId );

      // Si el objeto no fue salvado en la operacion actual...
      if (!$obj->isSaved( $sessId ))
      {
         // Nuevo: solo salva si se ha cambiado un atributo o una relacion hasOne (dirty)
         if ($obj->isDirtyOne())
         {
             // La relacion con los hasOne, o sea el id, se salva como atributo simple en save_object.
             // Este codigo de abajo solo verifica si hay que salvar en cascada cada objeto hasOne que tenga asociado.
            
             //asOne no necesita tablas intermedias (salvar la referencia)
             // Retorna los valores no nulos de hasOne
             $sassoc = $obj->getSimpleAssocValues(); // TODO?: Podria chekear si debe o no salvarse en cascada...
             foreach ( $sassoc as $attrName => $assocObj )
             {
                // ojo el objeto debe estar cargado (se verifica eso)
                if ( $assocObj !== PersistentObject::NOT_LOADED_ASSOC )
                {
                   //echo "=== PO loaded: $attrName<br/>";
                   
                   // Si se detecta un loop en el salvado del modelo.
                   // FIXME: deberia salvar solo si el objeto soy owner del relacionado.
                   if ( $assocObj->isLoopMarked( $sessId ) )
                   {
                      //Logger::getInstance()->pm_log("LOOP DETECTADO " . get_class($obj) . " " . get_class($assocObj));
    
                      // Agrega al objeto un callback para que se llame cuando termine de salvarse, para salvar el objeto hasOne asociado.
                      // Se salva el objeto actual sin el asociado (assocObj viene a ser instancia de A del modelo A -> B -> C -> A, donde obj viene a ser instancia de C).
                      // Esto deja a obj inconsistente, pero se arregla con el callback cuando termina de salvar a A, se actualiza la referencia de C a A.
    
                      // =============================================================================
                      // Se empezo a salvar desde A, se quiere salvar C que a su vez necesita A.
                      // $assocObj es A.
                      // $obj es C.
    
                      // 1. Actualizar ids de hasOne. // update_simple_assocs
                      $callb_update = new Callback();
                      $callb_update->set( $obj, 'update_simple_assocs', array() );
    
                      // FIXME (posible bug TICKET #4.1): OJO!, este save deberia ser un save simple (no salvar nada en cascada) y hacerce obligatoriamente, sin considerar el id de session...
                      // 2. Salvar el objeto. Llama a save del PO que es el wrapper del PM...
                      $callb_save = new Callback();
                      $callb_save->set( $obj, 'single_save', array() ); // Intento solucion TICKET #4.1
    
                      // Registro los callbacks en A, para que cuando se salve, se actualice C con su id.
                      $assocObj->registerAfterSaveCallback( $callb_update );
                      $assocObj->registerAfterSaveCallback( $callb_save );
    
                      // No se sigue salvando en cascada el objeto asociado xq ya se quiso salvar y se llego
                      // a un loop, se corta el loop y se salvan los objetos con los datos que tienen, y los
                      // datos que no se tienen se salvan en callbacks.
                      // =====================================================================================
                   }
                   else // Si no es un loop en el modelo, salva en cascada como siempre...
                   {
                      // El objeto puede estar salvado en otra sesion, por ejemplo se crea y salva, y luego se asocia al obj.
                      // La condicion es: si no esta salvado en esta session o esta sucio, salvo en cascada.
                      if ($obj->isOwnerOf( $attrName ) && (!$assocObj->isClean() || !$assocObj->isSaved( $sessId )))
                      {
                         //Logger::getInstance()->pm_log("PM::save_assoc save_cascade de ". $assocObj->getClass() .__LINE__);
                         
                         // Valido el objeto antes de intentar salvarlo
                         // La excepcion se catchea en el save y hace rollback de todo el save en cascada.
                         if (!$assocObj->validate()) throw new Exception("El objeto ". $assocObj->getClass() ." (".$assocObj->getId().") no valida. ". __FILE__." ".__LINE__);
                         
                         // hasOne no necesita tablas intermedias (salvar la referencia)
                         // salva objeto y sus asociaciones.
                         $this->save_cascade( $assocObj, $sessId );
                      }
                   }
                } // Si el hasOne esta cargado. Sino esta cargado, no hago nada (la relacion se actualiza en save_object).
             } // Para cada objeto asociado

             // ------------------------------------------------------------------------------------------------------------------
             // VERIFY: Como y donde se setean los atributos de id de las referencias!!
             // (tendria que hacerse en DAL verificando que el atributo corresponde a una asociacion hasOne)
             //
             // Aca tengo los ids de los hasOne y puedo salvar las referencias desde obj a ellos.
             // FIXME!!!!!: TENGO QUE SALVAR ANTES LOS hasOne para tener sus ids y setear los atributos generados "email_id" ...!!!
             $obj->update_simple_assocs(); // Actualiza los atributos de referencia a objetos de hasOne (como "email_id")
         
         } // si la instancia esta dirty
         
         //Logger::struct( $obj , "PRE PM.save_object en PM.save_cascade");
         //Logger::getInstance()->pm_log("PM::save_assoc save_object ". $obj->getClass() ." @".__LINE__);
         
         // salva el objeto simple, verificando restricciones en la instancia $obj
         // FIXME: esta operacion no verifica restricciones, se deberia validar el objeto antes de salvar.
         //        la validacion para el objeto raiz de la estrucura se hace en PO, pero no para el resto
         //        de los objetos asociados que se salvan en cascada
         // FIXME: el problema es que si falla la validacion de un objeto salvado en cascada, deberia
         //        abortar todo el save, o sea ser transaccional, y esto todavia no esta soportado.
         $this->save_object( $obj, $sessId );

         // Si se han modificado los hasMany
         if ($obj->isDirtyMany())
         {
             $massoc = $obj->getManyAssocValues(); // Es una lista de listas de objetos.
             foreach ($massoc as $attrName => $objList)
             {
                $ord = 0;
                
                //Logger::getInstance()->pm_log("save_cascade foreach hasManyAssoc: ". $attrName ." ". __FILE__ ." ". __LINE__ );
                
                foreach ( $objList as $assocObj )
                {
                   // Problema con cascada hasMany: a1 -> b1 -> c1 -> a1
                   // cuando c1 quiere salvar a a1 no entra aca, eso esta bien, pero deberia salvarse la relacion c1 -> a1...
                   // No se cual es la condicion para salvar la relacion solo, voy a intentar solo decir que c1 es owner de a1 a ver que pasa...
                   if ( $obj->isOwnerOf( $attrName ) )
                   {
                      //Logger::getInstance()->pm_log("PM::save_assoc ". $obj->getClass()." isOwnerOf $attrName. " .__LINE__);
                      
                      // FIXME ?: por que aca no es igual que en las relaciones hasOne?
                      
                      // FIXME: Es probable que el assocObj no se haya salvado en esta sesion y que este salvado.
                      //        Se pudo haber creado y salvado antes, y luego asociado a hasMany del obj.
                      //        Habria que preguntar si NO esta salvado en esta session o si esta dirty.
                      if (!$assocObj->isClean() || !$assocObj->isSaved( $sessId )) 
                      {
                         // Valido el objeto antes de intentar salvarlo
                         // La excepcion se catchea en el save y hace rollback de todo el save en cascada.
                         if (!$assocObj->validate()) throw new Exception("El objeto ". $assocObj->getClass() ." (".$assocObj->getId().") no valida. ". __FILE__." ".__LINE__);
                         
                         // salva objeto y sus asociaciones.
                         $this->save_cascade( $assocObj, $sessId );
                         //Logger::getInstance()->pm_log("PM::save_cascade objeto guardado: ". $assocObj->getClass(). " ". $assocObj->getId(). " " .__LINE__);
                      }
    
                      //Logger::getInstance()->pm_log("PM::save_assoc save_assoc de ". $obj->getClass(). " ". $assocObj->getClass(). " " .__LINE__);
                      
                      // El objeto puede estar salvado y la relacion no.
                      // Actualiza tabla intermedia.
                      // Necesito tener, si la relacion es bidireccional, el nombre del atributo de assocObj que tiene Many obj, podria haber varios!
                      $this->save_assoc( $obj, $assocObj, $attrName, $ord ); // Se debe salvar aunque a1 este salvado (problema loop hasmany)
                   }
                   
                   $ord++;
                } // para cada objeto dentro de una relacion hasMany
             } // para cada relacion hasMany
         } // si tiene dirtyMany
      } // if is_saved obj
      
      // Termina de guardar el objeto, limpia los bits de dirty.
      $obj->resetDirty();
      
   } // save_cascade

  /**
   * save solo sirve para arrancar la session, la que hace el trabajo de salvar realmente es save_cascade, que salva todo el modelo.
   */
   public function save( PersistentObject $obj )
   {
      Logger::getInstance()->pm_log("PM:save " . get_class($obj));
      $sessId = time()."_". rand()."_". rand(); // se genera una vez y se mantiene por todo el save. Se agregaron rands porque para saves consecutivos se hacia muy rapido y la sessId quedaba exactamente igual.
      $this->save_cascade( $obj, $sessId );
   }

   /**
    * Se utiliza en get_object y en listAll.
    * @param Class $classLoaded subclase de PersistentObject por la que se quiere cargar, por ejemplo se puede cargar por A pero la instancia real es una subclase de A, p.e. G. 
    * @param array $attrValues array asociativo resultante de cargar una fila de una tabla por su id, es exactamente lo que devuelve $dal->get( $tableName, $id ).
    * @return PersistentObject objeto referenciado por los datos, si es MTI devuelve el objeto completo de la clase correcta.
    */
   private function get_mti_object_byData( $classLoaded, $attrValues )
   {
      Logger::getInstance()->pm_log("PM.get_mti_object_byData: CLASS LOADED: ". $classLoaded ." ". print_r($attrValues, true));

      // $attrValues['id'] es el identificador de todas las instancias parciales de MTI.

      // Nueva instancia de la clase real.
      $cins = new $attrValues["class"](array(), true); // Intancia para hallar nombre de tabla (solo para eso, no se usa luego).
      
      // Si no esta mapeado en la misma (pruebo con cins porque con obj puede no funcionar si es una clase de nivel 1).
      // O sea, si $persistentClass es A o A1 me dice que MTI es false aunque sea una instancia real de C, C1, G o G1.
      if ( MultipleTableInheritanceSupport::isMTISubclassInstance( $cins ) )
      {
         //Logger::getInstance()->pm_log("ES MTI: " . __FILE__ . " " . __LINE__);
         
         // 2.1: Cargar la ultima instancia parcial en la estructura de herencia.
         //$superclases = ModelUtils::getAllAncestorsOf( $attrValues["class"] ); // $attrValues["class"] es la ultima en la estructura del carga de multiple tabla, puede tener subclases pero se guardan en la misma tabla que ella. Por eso necesito solo los padres xq son los que se pueden guardar en otras tablas.
         //$superclases[] = $attrValues["class"];

         // SOLO DEBE HACERSE SI LA CLASE $persistentClass no es la misma que la que dice su atributo "class"...
         // En ese caso, $sc_partial_instance es igual a los attrValues cargados al principio.
         $sc_partial_row = NULL; // Matriz de datos simples
         if ( self::isMappedOnSameTable($attrValues['class'], $classLoaded) )
         {
            $sc_partial_row = $attrValues; // Ya es la ultima instancia, no cargo nada mas.
         }
         else
         {
            // Necesito cargar porque el ultimo registro esta en otra tabla.
            
            // FIXME: esto se puede simplificar sabiendo que todas las instancias parciales de MTI tienen el mismo id.
            $tableName = YuppConventions::tableName( $cins );
            
            // Ahora el id en la clase de nivel 1, y el de la instancia final, es siempre el mismo.
            // Por eso, pido directo por id
            $sc_partial_row = $this->dal->get($tableName, $attrValues['id']);
         }
         
         // MERGE DE LA INSTANCIA CARGADA CON $sc_partial_instance
         //
         // AHORA DEBERIA VER, con esta instancia cargada, si falta cargar otra instancia (aparte de la primera que cargue y esta).
         // Si hay, hago un bucle cargando y mergeando.
         // 
         // PARA MERGE, USAR: MTI::mergePartialInstances( $po_ins1, $po_ins2 )
          
         // VERIFY: capaz hacer merge en cada cargada es poco performante, hay que tomar tiempos y considerar otras alternativas.
         $attrValues = array_merge( $attrValues, $sc_partial_row ); // AttrValues va recolectando los atributos, en este caso el id de $sc_partial_instance esta bien que sobreescriba el id de la otra instancia parcial xq importa que quede el id de la ultima clase de la estructura de herencia.
          

         // Obtiene las instancias parciales para todas las superclases
         $superclasses = ModelUtils::getAllAncestorsOf($attrValues['class']);
         foreach ($superclasses as $mtiClass)
         {
            // Solo quiero las superclases que no se hayan cargado, $persistentClass es la primera que se carga.
            if ($mtiClass !== $classLoaded)
            {
               $tableName = YuppConventions::tableName( $mtiClass );
               $scAttrValues = $this->dal->get( $tableName, $attrValues['id'] ); // Se usa el mismo id para todas las instancias parciales
               $attrValues = array_merge( $attrValues, $scAttrValues );
            }
         }
   
         // $attrValues deberia tener todos los atributos simples de las instancias parciales cargadas.
          
      } // if instancia parcial

      // Soporte para herencia. (TODO: necesito mas que esto para multiples tablas)
      $realClass = $attrValues['class'];
      
      return $this->createObjectFromData( $realClass, $attrValues );
   
   } // get_mti_object_byData

   // Trae un objeto simple sin asociaciones hasMany y solo los ids de hasOne.
   public function get_object( $persistentClass, $id )
   {
      Logger::getInstance()->pm_log("PM.get_object " . $persistentClass . " " . $id);

      // Si llega aqui es porque ya se verifico que no estaba en ArtifactHolder.

      // 1: Cargar la instancia que me piden.

      $obj = new $persistentClass(array(), true); // Intancia para hallar nombre de tabla (solo para eso, no se usa luego).
      $tableName = YuppConventions::tableName( $obj );
      $attrValues = $this->dal->get( $tableName, $id );
      
/*
 * VER: Otra posible solucion para mti, es que cargue solo los atributos que tengo en esa tabla, 
 * y luego cargue lo demas lazy, o sea: 
 * si a PO le pido un getXX y me doy cuenta que XX no lo tengo (porque pude no haberlo cargado) 
 * verifico si esta en otra tabla de una instancia parcial y ahi cargo la instancia parcial. 
 * (o sea, lazy load para atributos simples)
 */
 
      // http://code.google.com/p/yupp/issues/detail?id=132
      if (count($attrValues) == 0) return NULL;
      
      // 2: Verificar si es una instancia parcial y cargar las demas instancias parciales, mergear, y generar la instancia final.
      
      return $this->get_mti_object_byData( $persistentClass, $attrValues );

   } // get_object
   
   /**
    * FIXME: $class viene en data['class'].
    * Crea una instancia del objeto a partir de informacion dada por DAL.
    */
   private function createObjectFromData( $class, $data )
   {
      Logger::getInstance()->pm_log("PM.createObjectFromData " . $class );
      
      // $data son $attrValues.
      
      $obj = new $class(); // Instancia a devolver, instanciado en la clase correcta.

      // Carga atributos simples
      foreach ($data as $colname => $value)
      {
         // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
         // ACA ESTA EL PROBLEMA AL CARGAR QUE DICE QUE NO hasAttribute para normalizedName...
         
// FIX rapido porque hasAttribute no busca en los atributos ocn nombre normalizado como columna.
// En todos los lugares donde pregunte por hasAttribute puede haber el mismo problema.
// Tengo un problema cuando la clase no tiene el atributo declarado en ella pero si esta declarado en la 
// superclase... me tira que no existe el atributo.
//       if ($obj->hasAttribute($attr)) // Setea solo si es un atributo de el.
//       {
         // Obtiene el nombre del atributo para setearlo, si es NULL la clase no tiene ese atributo.
         
         $attr = $obj->getAttributeByColumn( $colname );
         if ( !is_null($attr) )
         {
            // TODO: Ver como se cargan los NULLs, por ahora se setean... como debe ser?
            
            // Deshace el addslashes del inser_query y update_query de DAL.
            // FIXME: esto deberia ser tambien responsabilidad de DAL.
            if ( is_string( $value ) ) $value = stripslashes($value);
            
            $obj->aSet( $attr, $value );
         }
      }
      
      // El tipo del id que se carga desde la base es un string php
      $obj->aSet( 'id', (int)$obj->getId() );
      
      // Apaga las banderas que se prendieron en la carga
      $obj->resetDirty();

      return $obj;
   }
   
   /**
    * Operacion inversa a createObjectFromData, sirve para extraer los datos para de mandarselos a DAL.
    */
   private function getDataFromObject( $obj )
   {
      Logger::getInstance()->pm_log("PM.getDataFromObject");
      
      $data = array();
      $attrs = $obj->getAttributeTypes();
      foreach ( $attrs as $attr => $type )
      {
         $data[$attr] = $obj->aGet( $attr );
      }
      
      return $data;
   }

   /**
    * Obtiene solo una asociacion.
    */
   public function get_many_assoc_lazy( PersistentObject $obj, $hmattr )
   {
      Logger::getInstance()->pm_log("PM.get_many_assoc_lazy " . get_class( $obj ) . " " . $hmattr);

      // TODO: tengo que cargar solo si tiene deleted en false en la tabla de join.

      // FIXME: esta clase podria ser superclase de la subclase que quiero cargar.
      //        tengo que ver en la tabla de que tipo es realmente y cargar una instancia de eso. 
      $hmattrClazz = $obj->getType( $hmattr );
            
      // (***)
      $relObjIns = new $hmattrClazz(); // Intancia para hallar nombre de tabla.
      $relObjTableName = YuppConventions::tableName($relObjIns);

      $relTableName = "";
      $obj_is_owner = $obj->isOwnerOf( $hmattr );
      if($obj_is_owner)
      {
         $relTableName = YuppConventions::relTableName($obj, $hmattr, $relObjIns);
      }
      else
      {
         // Si no soy owner tengo que pedir el atributo...
         $ownerInstance = $relObjIns;
         
         // FIXME: si ownerInstance tiene 2 relaciones HM con la class del $obj, este metodo retorna NULL.
         $ownerAttrNameOfSameAssoc = $ownerInstance->getHasManyAttributeNameByAssocAttribute( get_class($obj), $hmattr );
         
         //echo "Call to relTableName hmattr=$hmattr ownerAttr=$ownerAttrNameOfSameAssoc ".__FILE__.__LINE__."<br/>";
         
         $relTableName = YuppConventions::relTableName( $ownerInstance, $ownerAttrNameOfSameAssoc, $obj );
      }

      // =================================================================================
      // (***)
      // FIXME: deberia hacer un join con la tabla de referencia y la 
      //        tabla destino para traer todos los atributos y no tener 
      //        que hacer consultas individuales para cargar cada objeto.
      //
      // =================================================================================

      // =================================================================================
      // QIERO PEDIR SOLO LOS ELEMENTOS DE ObjectReference, para poder recorrerlo y ver si ya tengo objetos cargados,
      // y cargo solo los que no estan cargados. Seteo todos los objetos al atributo hasMany del objeto.

      YuppLoader::load('core.db.criteria2', 'Query');
      $q = new Query();
      $q->addFrom($relTableName, 'ref'); // person_phone ref // FIXME: ESTO ES addFrom.
      $q->addFrom($relObjTableName, 'obj');
      
      
      // FIXME: quiero todos los atributos...
      // Se agregan los atributos de la clase como proyeccion de la query.
      // Solo quiero los atributos de OBJ, agrego sus atributos como proyecciones de la consulta.
      /* esto seleccionaba solo los atributos declarados en la clase.
      foreach( $_obj->getAttributeTypes() as $attr => $type )
      {
         //$q->addProjection("obj", $attr);
         // TODO: normalizar en la query mismo
         $q->addProjection( "obj", DatabaseNormalization::col($attr) );
      }
      */
      $q->addProjection( 'obj', '*' ); // Todos los atributos de la tabla con alias "obj".
      
      // Necesito saber el nombre del atributo de los ids asociados.
      $hm_assoc_attr = "owner_id"; // FIXME: poner el string en una clase de convensiones de yupp

      // Los ids de todas las instnacias parciales de la clase declarada en el atributo
      // hasMany, van a ser todos iguales, por eso uso el id del objeto que viene.
      $obj_id = $obj->getId();

      // Tengo que ver el objeto en la tabla de referehcia si es el owner_id o el ref_id
      if ( $obj_is_owner )
      {
          // FIXME: poner el string en una clase de convensiones de yupp
         $hm_assoc_attr = 'ref_id'; // yo soy el owner entonces el asociado es ref.
         $q->setCondition(
           Condition::_AND()
             ->add( Condition::EQ('ref', 'owner_id', $obj_id) ) // ref.owner_id = el id del duenio (person_phone.owner_id = obj->getId)
             ->add( Condition::EQA('obj', 'id', 'ref', 'ref_id') ) // JOIN
         );
      }
      else // Aca obj es ref_id y class es owner_id !!! (soy el lado debil)
      {
         $q->setCondition(
            Condition::_AND()
              ->add( Condition::EQ('ref', 'ref_id', $obj_id) ) // ref.owner_id = el id del duenio (person_phone.ref_id = obj->getId)
              ->add( Condition::EQ('ref', 'type', ObjectReference::TYPE_BIDIR) ) // type = bidir
              ->add( Condition::EQA('obj', 'id', 'ref', 'owner_id') ) // JOIN
         );
      }
      
      // ==========================================================================
      // Desde v0.1.6: soporte para tipos de hasMany
      // Si es de tipo lista, debe donsiderar el orden.
      
      if ( $obj->getHasManyType($hmattr) === PersistentObject::HASMANY_LIST )
      {
         $q->addOrder("ref", "ord", "ASC"); // Orden ascendente por atributo ORD de la tabla intermedia.
      }
      
      Logger::getInstance()->pm_log("PersistentManager.get_many_assoc_lazy query ". __FILE__ ." ". __LINE__);
      
      // Trae todos los objetos linkeados... (solo sus atributos simples)
      $data = $this->dal->query( $q );

      // FIN QUERY...
      
      $wasDirty = $obj->isDirtyMany();

      // Ojo, se prenden bits de dirty (es necesario detectar si no estaba dirty antes, para saber si puede limpiar).
      $obj->aSet( $hmattr, array() ); // Inicalizo lista xq seguramente estaba en NOT_LOADED.

      foreach ( $data as $many_attrValues ) // $many_attrValues es un array asociativo de atributo/valor (que son los atributos simples de una instancia de la clase)
      {
         /* Esta cargado?
          * $rel_obj_id = $many_attrValues[ $hm_assoc_attr ]; // El codigo que usa esta linea esta comentado...
          * if ( ArtifactHolder::getInstance()->existsModel( $hmattrClazz, $rel_obj_id ) )
          * {
          *    $rel_obj = ArtifactHolder::getInstance()->getModel( $hmattrClazz, $rel_obj_id );
          * }
          * else
          * {
          *    $rel_obj = $this->get_object( $hmattrClazz, $rel_obj_id ); // Carga solo el objeto, sin asociaciones.
          *    ArtifactHolder::getInstance()->addModel( $rel_obj ); // FIXME: ArtHolder deberia referenciarse solo del PM!!!!!
          * }
          */
         
         // Esto soluciona la carga de autorrelacion desde una subclase.
         // B(heredaDe)A y A(hasMany)A, y quiero cargar B que a su vez tiene asociados varios Bs.
         
         // FIXME: esta clase podria ser superclase de la subclase que quiero cargar.
         //        tengo que ver en la tabla de que tipo es realmente y cargar una instancia de eso. 
         // (***)
         //$rel_obj = $this->createObjectFromData( $hmattrClazz, $many_attrValues );
         
         if ($many_attrValues['class'] === $hmattrClazz)
         {
            // FIXME: si el rel_obj tiene hasOne, y hereda de otra clase, no se cargan los hasOne.
            //Logger::getInstance()->pm_log("Caso1: $hmattrClazz ". __FILE__ ." ". __LINE__);
            //Logger::struct($many_attrValues, "many_attrValues");
            
            //echo "   la clase es la misma que la declarada<br/>";
            $rel_obj = $this->createObjectFromData( $hmattrClazz, $many_attrValues );
         }
         else
         {
            //Logger::getInstance()->pm_log("Caso2: [$hmattrClazz / ". $many_attrValues['class'] ."] " . __FILE__ ." ". __LINE__);
            //Logger::struct($many_attrValues, "many_attrValues");
            
            //echo "   la clase NO es la misma que la declarada<br/>";
            // TODO: deberia cargar los atributos declarados en la clase $many_attrValues['class'], que estan en otra tabla que la que acabo de cargar.
            //       por ejemplo el id cargado es el de una superclase no el de la clase que deberia ser la instancia.
            $rel_obj = $this->get_mti_object_byData( $hmattrClazz, $many_attrValues );
         }

         $obj->aAddTo( $hmattr, $rel_obj );
      }
      
      if (!$wasDirty) $obj->resetDirtyMany();

   } // get_many_assocs_lazy

   /*
    * Get con soporte para herencia:
    * A <- B <- C1, C2 estructura de herencia.
    * - pido atributos de una fila por id
    * - creo instancia de la clase y seteo atributos
    *   - si pido por ejemplo, por la clase B, deberia pedir tambien atributos de C1 y C2, y
    *     deberia instanciar cada clase con su clase real para que no me haga problemas al
    *     setear los atributos, ahi deberia funcionar bien y si hay errores es porque estoy
    *     seteando un atributo en una clase que no lo tiene...
    *
    * tons:
    * - pido fila
    * - veo clase de la fila
    *   - si es la misma clase por la que estoy pidiendo
    *     - hago instancia de esa clase (de la fila) y cargo como siempre
    *   - si no (puede ser herencia)
    *     - Verifico que la fila es de una subclase de la clase por la que pido (si no es asi es un error enorme!!! xq hizo save de algo mal)
    *     - Hago instancia de la clase de la fila y cargo.
    *
    */
   // Hace select por el id y devuelve null si no encuentra.
   public function get( $persistentClass, $id )
   {
      Logger::getInstance()->pm_log("PM:get ". $persistentClass .":". $id);

      //////////////////////////////////////////////////////////
      //
      // 1. eager: traigo todo el modelo.
      //           cargo el objeto
      //           cargo sus clases asociadas hasOne
      //           cargo sus clases asociadas hasMany
      //
      //              para cada objeto asociado hasOne
      //                 cargo sus clases asociadas hasOne
      //                 cargo sus clases asociadas hasMany
      //
      //                     ...
      //
      //              para cada objeto asociado hasMany
      //                 ...
      //                 ...
      //
      //////////////////////////////////////////////////////////

      $obj = NULL;

      if ( ArtifactHolder::getInstance()->existsModel( $persistentClass, $id ) ) // Si ya esta cargado...
      {
         $obj = ArtifactHolder::getInstance()->getModel( $persistentClass, $id );
      }
      else
      {
         // Define la estrategia con la que se cargan los objetos...
         $obj = $this->po_loader->get($persistentClass, $id); // Llama primero a get_objects.
         
         // Ya se hace reset en createObjectFromData, el metodo que se usa para crear el objeto en la carga.
         // FIXME: solo si la clase estaba limpia antes de la operacion
         //$obj->resetDirty(); // Apaga las banderas que se prendieron en la carga

         // http://code.google.com/p/yupp/issues/detail?id=132
         if ($obj != NULL)
            ArtifactHolder::getInstance()->addModel( $obj ); // Lo pongo aca para que no se guarde luego de la recursion de las assocs...
      }
      
      return $obj;
   }

   /**
    * Fixme, deberia recibir solo la clase, no una instancia.
    * @return Devuelve todos los elementos de la clase dada, que no estan aliminados,
    *         segun los criterios de paginacion y ordenamiento dados.
    */
   // Si max == -1 traigo todos los items.
   public function listAll( $ins, ArrayObject $params )
   {
      Logger::getInstance()->pm_log("PM::listAll ". $ins->getClass());

      $objTableName = YuppConventions::tableName( $ins ); // ins se usa solo para sacar el nombre de la tabla y para sacar los nombres de las subclases.

      // Quiero solo los registros de las subclases y ella misma.
      $class = get_class( $ins );
      $scs = ModelUtils::getAllSubclassesOf( $class );
      $scs[] = $class;

      // Definicion de la condicion.
      $cond = Condition::_AND(); // Para hacer and con la condicion de deleted false y la de herencia

      // Condicion para soportar carga de herencia.
      if ( count($scs) == 1 )
      {
         $cond->add( Condition::EQ($objTableName, "class", $scs[0]) );
      }
      else
      {
          $cond_or = Condition::_OR();
          foreach ($scs as $subclass)
          {
              $cond_or->add( Condition::EQ($objTableName, "class", $subclass) );
          }
          $cond->add( $cond_or );
      }

      // OBS: No devuelve elementos eliminados (deleted=false)
      // FIXME: deberia tener un parametro "loadDeletedTo" que indique si quiere
      // cargar las instancias eliminadas de forma logica, pueden haber aplicaciones
      // que les interese acceder a las instancias eliminadas de forma logica.
      //
      // FIXME: Si le pongo false a la RV no aparece nada y me tira consulta erronea.
      // Tendria que ponerle un convertidor de true/false a 1/0...
      $cond->add( Condition::EQ($objTableName, "deleted", 0) ); // FIXME: en postgres boolean se verifica contra '0' no contra 0.

      $params['where'] = $cond;

      $allAttrValues = $this->dal->listAll( $objTableName, $params );
      // FIXME: Ahora me devuelve todos las columnas, necesito solo ID y CLASS, luego con eso pido todo lo demas
      // usando otras funciones que ya tengo, sobre todo para respetar la estrategia de carga.
      // Pero tambien es cierto que de esta forma ya se carga todos los atributos simples de una y luego podria ver
      // segun la estrategia si cargo las asociaciones con otras clases, para esto el proceso de carga deberia estar
      // mas estandarizado, con funciones que me acepten por ejemplo un mapa con los atributos simples y tenga el loop
      // que aca aparece seteandolos a determinada clase (eso esta en get_object tambien), hay que ver, por ahora lo dejo
      // sin esa posible optimizacion, que se que funciona. (PERO AHORA TRAR LOS ATRIBUTOS 2 VECES xq el dal->listAll los trae tambien).


      // =============================== ==========================================
      // Ahora esta cargando en cascada... deberia cargar segun LoadEstrategy... ??? ESTO SIGUE SIENDO ASI???

      // FIXME: esto no soporta MTI, deberia hacerse como se hace en get_object, preguntar 
      //        si es MTI, si es, ver si es la ultima instancia, si no es, cargar la ultima 
      //        intancia, y luego cargar todas las otras intancias parciales, para por 
      //        ultimo armar el objeto completo. (*)

      $res = array(); // Lista de objetos
      foreach ($allAttrValues as $row)
      {
         $persistentClass = $row['class']; // soporte de herencia!!!!

         // Carga considerando estrategia... y se fija en el holder si ese objeto no esta ya cargado.

         // Ya cargo toda la informacion, no necesito consultar ArtifactHolder de nuevo, uso createObjectFromData.
         //  (*) Soporte para MTI como en get_object (incluye a createObjectFromData).
         $obj = $this->get_mti_object_byData( $class, $row );

         $res[] = $obj;

         // TODO: ver si get_mti_object_byData considera la estrategia de carga: $this->po_loader
         //       si es lazy esta bien que no cargue, pero si es eager deberia cargar.
         //$this->get_simple_assocs( $obj ); // OK
         //$this->get_many_assocs( $obj ); // TODO: falta crear los objetos y linkearlos
      }

      return $res;

   } // listAll


   /**
    * Hace una consulta y devuelve las filas correspondientes a los registros que matchean el criterio.
    * Es una lista de esos registros, por eso es una matriz.
    */
   private function findByAttributeMatrix( PersistentObject $instance, Condition $condition, ArrayObject $params )
   {
      //Logger::getInstance()->pm_log("PM::findByAttributeMatrix ". $instance->getClass() ." : " . __FILE__."@". __LINE__);

      // FIXME: misma logica que en listAll, reutilizar codigo.      
      $tableName = YuppConventions::tableName( $instance );

      // Quiero solo los registros de las subclases y ella misma.
      $class = get_class( $instance );
      $scs = ModelUtils::getAllSubclassesOf( $class );
      $scs[] = $class;

      // La condicion total es la que me pasan AND CONDICION_DE_NOMBRES_DE_SUBCLASES AND NO_ELIMINADO

      // Definicion de la condicion.
      $cond_total = Condition::_AND();

      // CONDICION_DE_NOMBRES_DE_SUBCLASES
      if ( count($scs) == 1 )
      {
         $cond_total->add( Condition::EQ($tableName, "class", $scs[0]) );
      }
      else
      {
         $cond_or = Condition::_OR();
         foreach ($scs as $subclass)
         {
            $cond_or->add( Condition::EQ($tableName, "class", $subclass) );
         }
         $cond_total->add( $cond_or );
      }

      // =====================================================================================
      // NO_ELIMINADO
      // FIXED: Si viene una condicion deleted, no agregarla. p.e. puedo pedir deleted = true
      //if ($condition->hasCondForAttr('deleted')) echo 'tiene para deleted';
      //else echo 'no tiene para deleted';
      //
      if (!$condition->hasCondForAttr('deleted'))
         $cond_total->add( Condition::EQ($tableName, 'deleted', 0) );

      // CRITERIO DE BUSQUEDA
      $cond_total->add( $condition );

      $params['where'] = $cond_total;

      // Valores de todos los atributos
      // FIXME: AHORA TIRA TODOS LOS ATRIBUTOS Y NECESITO SOLO CLASS e ID.
      return $this->dal->listAll( $tableName, $params );
      
   } // findByAttributeMatrix


   /**
    * Devuelve una lista de PO correspondientes a la consulta realizada.
    */
   public function findBy( PersistentObject $instance, Condition $condition, ArrayObject $params )
   {
      //Logger::getInstance()->pm_log("PM::findBy ". $instance->getClass() ." : " . __FILE__."@". __LINE__);
      
      // Consulta para saber la clase real (subclase concreta) del objeto que se está pidiendo
      $allAttrValues = $this->findByAttributeMatrix( $instance, $condition, $params ); //$dal->listAll( $tableName, $params ); // FIXME: AHORA TIRA TODOS LOS ATRIBUTOS Y NECESITO SOLO CLASS e ID.

      $res = array(); // Lista de objetos
      foreach ($allAttrValues as $row)
      {
         // FIXED: http://code.google.com/p/yupp/issues/detail?id=110
         // Si la clase real (row[class]) es distinta a la clase por la que busco ($instance->getClass()),
         // la clase por la que busco será una superclase de la real.
         
         // Carga considerando estrategia... y se fija en el holder si ese objeto no esta ya cargado.
         $obj = NULL;
         if ( ArtifactHolder::getInstance()->existsModel( $row['class'], $row['id'] ) ) // Si ya esta cargado...
         {
            $obj = ArtifactHolder::getInstance()->getModel( $row['class'], $row['id'] );
         }
         else
         {
            $obj = $this->po_loader->get($row['class'], $row['id']); // Define la estrategia con la que se cargan los objetos...
            ArtifactHolder::getInstance()->addModel( $obj ); // Lo pongo aca para que no se guarde luego de la recursion de las assocs...
         }

         $res[] = $obj;
      }

      return $res;
      
   } // findBy
   

   public function findByQuery( Query $q )
   {
      Logger::getInstance()->pm_log("PM::findByQuery");
      return $this->dal->query( $q );
   }

   
   /**
    * Devuelve una lista de $searchClass, tal que:
    * 1. $searchClass tiene un atributo hasMany $hasManyAttr a la clase $byClass
    * 2. $searchClass tiene una instancia de $byClass en $hasManyAttr con $byId
    */
   public function findHasManyReverse($searchClass, $hasManyAttr, $byClass, $byId)
   {
      $sins = new $searchClass(array(), true); // PHP 5.3
      if (!$sins->hasAttribute( $hasManyAttr )) // FIXME: se debe fijar si existe y es hasMany, aqui solo se fija que exista.
      {
         throw new Exception("El atributo hasMany $hasManyAttr no existe");
      }
      
      // tabla de join
      $relins = new $byClass(array(), true);
      $joinTableName = YuppConventions::relTableName( $sins, $hasManyAttr, $relins );
      
      // tabla de esta clase
      $objTableName = YuppConventions::tableName( $sins );
      
      // quiero owner_id de la tabla de join, por byId
      // luego hacer join con la tabla de $this
      //$cond = Condition::EQ($joinTableName, "byId", $byId);
      
      // similar PM.get_many_assoc_lazy
      YuppLoader::load('core.db.criteria2', 'Query');
      $q = new Query();
      $q->addFrom($joinTableName, 'ref')
         ->addFrom($objTableName, 'obj')
         ->addProjection( 'obj', '*' )
         ->setCondition(
            Condition::_AND()
             ->add( Condition::EQA('obj', 'id', 'ref', 'owner_id') ) // JOIN
             ->add( Condition::EQ('ref', 'ref_id', $byId) ) // Busca por la referencia
         );
      
      $data = $this->findByQuery( $q ); // PM 760
      
      $result = array();
      
      foreach ( $data as $many_attrValues ) // $many_attrValues es un array asociativo de atributo/valor (que son los atributos simples de una instancia de la clase)
      {
         if ($many_attrValues['class'] === $byClass)
         {
            //echo "   la clase es la misma que la declarada<br/>";
            $rel_obj = $this->createObjectFromData( $byClass, $many_attrValues );
         }
         else
         {
            //echo "   la clase NO es la misma que la declarada<br/>";
            $rel_obj = $this->get_mti_object_byData( $byClass, $many_attrValues );
         }

         $result[] = $rel_obj;
      }
      
      return $result;
   }
   
      
   /**
    * Ejecuta la query, e intenta crear instancias de $class con los registros obtenidos.
    * FIXME: Para poder hacer esto, la proyeccion de la query debe tene * y en from debe estar
    *        la tabla asociada a $class.
    */
   public function queryObjects($query, $class)
   {
      $data = $this->findByQuery( $query );
      
      $result = array();
      
      foreach ( $data as $many_attrValues ) // $many_attrValues es un array asociativo de atributo/valor (que son los atributos simples de una instancia de la clase)
      {
         if ($many_attrValues['class'] === $class)
         {
            $rel_obj = $this->createObjectFromData( $class, $many_attrValues );
         }
         else
         {
            $rel_obj = $this->get_mti_object_byData( $class, $many_attrValues );
         }

         $result[] = $rel_obj;
      }
      
      return $result;
   }


   // FIXME: El mundo seria mas sencillo si en lugar de pasarle la clase le paso la instancia...
   // ya que tengo que hacer un get_class para pasarle la clase y luego aca hago un new para crear una instancia...
   // para eso le paso la instancia que ya tengo y listo...
   public function exists( $persistentClass, $id )
   {
      return $this->dal->exists( YuppConventions::tableName( new $persistentClass() ), $id );
   }

   /**
    * Cuenta las instancias de una clase, sin contar las instancias eliminadas.
    */
   public function count( $ins )
   {
      Logger::getInstance()->pm_log("PM::count");
      return $this->countBy($ins, NULL);
   }


   /**
    * FIXME: pasarle la clase, no una instancia.
    */
   public function countBy( $ins, $condition )
   {
      Logger::getInstance()->pm_log("PM::countBy");
      
      $objTableName = YuppConventions::tableName( $ins );
      $params = array();

      // Quiero solo los registros de las subclases y ella misma.
      $class = get_class( $ins );
      $scs = ModelUtils::getAllSubclassesOf( $class );
      $scs[] = $class;

      // Definicion de la condicion.
      $cond_total = Condition::_AND();
      if ( count($scs) == 1 )
      {
         $cond_total->add( Condition::EQ($objTableName, "class", $scs[0]) );
      }
      else
      {
         $cond = Condition::_OR();
         foreach ($scs as $subclass)
         {
            $cond->add( Condition::EQ($objTableName, "class", $subclass) );
         }
         $cond_total->add( $cond );
      }
      
      // FIXED: igual que en findByAttributeMatrix usada por findBy
      // Si no tiene condicion deleted, le pone deleted false.
      if ( $condition == NULL || !$condition->hasCondForAttr('deleted'))
         $cond_total->add( Condition::EQ($objTableName, "deleted", 0) );

      // CRITERIO DE BUSQUEDA
      if ($condition != NULL) $cond_total->add( $condition );

      $params['where'] = $cond_total;

      return $this->dal->count( $objTableName, $params );
      
   } // countBy


   // Elimina un objeto de la base de datos.
   // Logical indica si la eliminacion es solo logica o es fisica.
   // FIXME: no es necesario pasar el id, lo tiene la instancia adentro.
   //public function delete( &$persistentInstance, $id, $logical )
   public function delete( $persistentInstance, $id, $logical )
   {
      Logger::getInstance()->pm_log("PM::delete");
      //Logger::add( Logger::LEVEL_PM, "PM::delete ". __FILE__."@". __LINE__ );
      
      // Se asume que la instancia ya es la ultima porque esta cargada con "get" 
      // o con "listAll" que garantiza que carga la instancia completa.
      
      // Borra el registro de la clase actual (no estaba incluida en los ancestors si es MTI)
      // Si no es MTI, este es el lunico delete que se hace
      $this->dal->delete( $persistentInstance->getClass(), $id, $logical );
      
      // Soporte MTI
      if (MultipleTableInheritanceSupport::isMTISubclassInstance( $persistentInstance ))
      {
         // Ahora tengo que pedir las superclases y para cada una, borrar la instancia parcial
         $superclasses = ModelUtils::getAllAncestorsOf($persistentInstance->getClass());
         foreach ($superclasses as $mtiClass)
         {
            $this->dal->delete( $mtiClass, $id, $logical ); // Todas las instancias parciales tienen el mismo id 
         }
      }
   } // delete

   
   /**
    * Se usa solo desde PO::aRemoveFrom y PO::aRemoveAllFrom.
    * 
    * ES COMO LO CONTRARIO DE SAVE_ASSOC, pero para solo un registro. save_assoc( PersistentObject &$owner, PersistentObject &$child, $ownerAttr )
    * Elimina la asociacion hasMany entre los objetos. (marca como eliminada o borra fisicamente el registro en la tabla de join correspondiente a la relacion entre los objetos).
    * attr1 es un atributo de obj1
    * attr2 es un atributo de obj2
    * attr1 y attr2 corresponden a los roles de la misma asociacion entre obj1 y obj2
    * attr1 y/o attr2 debe(n) ser hasMany
    * logical indica si la baja es fisica o logica.
    */
   public function remove_assoc( $obj1, $obj2, $attr1, $attr2, $logical = false )
   {
      Logger::getInstance()->pm_log("PM::remove_assoc");
      
      // TODO: Si la relacion es A(1)<->(*)B (bidireccional) deberia setear en NULL el atributo A y A_id de B.

      // Veo cual es el owner:
      $owner     = &$obj1;
      $ownerAttr = &$attr1;
      $child     = &$obj2;
      
      if ( $obj1->getClass() != $obj2->getClass() && $obj2->isOwnerOf( $attr1 ) ) // Si la asoc al obj1 es duenia de obj2
      {
         $owner     = &$obj2;
         $ownerAttr = &$attr2;
         $child     = &$obj1;
      }
      
      Logger::getInstance()->log( 'PM::remove_assoc owner '.$owner->getClass().', child '. $child->getClass() );

      // Para eliminar no me interesa el tipo de relacion (si esta instanciada bidireccional o unidireccional).
      // Quiero eliminar el que tenga ownerid y childid de los objetos que me pasaron.
      // (obs: entonces no permito mas de una relacion entre 2 instancias!)                               );

      // El id de la superclase, es igual que el id de la clase declarada en el hasMany, y el mismo que la instancia final
      // Por eso uso el id del objeto directamente
      $ref_id = $child->getId();

      Logger::getInstance()->log( 'PM::remove_assoc owner_id '.$owner->getId().', ref_id '. $ref_id );

      // se pasan instancias... para poder pedir el withtable q se setea en tiempo de ejecucion!!!!
      //
      $tableName = YuppConventions::relTableName( $owner, $ownerAttr, $child );

      // Necesito el id del registro para poder eliminarlo...
      // esto es porque no tengo un deleteWhere y solo tengo un delete por id... (TODO)
      YuppLoader::load( "core.db.criteria2", "Query" );
      $q = new Query();
      $q->addFrom( $tableName, "ref" )
        ->addProjection( "ref", "id" )
        ->setCondition( Condition::_AND()
          ->add( Condition::EQ("ref", "owner_id", $owner->getId()) )
          ->add( Condition::EQ("ref", "ref_id", $ref_id) ) );

      $data = $this->dal->query( $q );
      $id = $data[0]['id']; // Se que hay solo un registro...
                            // TODO: podria no haber ninguno, OJO! hay que tener en cuenta ese caso.

      $this->dal->deleteFromTable( $tableName, $id, $logical );

   } // remove_assoc
   
   
   // Metodos utilitarios para manejar mapeo de herencia multi-tabla
   
   /** FIXME: no deberia ser de PO? o de MTISup? no deberia estar en PM deberia ser algo del model utils o mti support.
    * Devuelve true si ambas clases se mapean en la misma tabla, las clases podrian ser 
    * superclase y subclase, ser clases primas, hermanas o no tener relacion alguna.
    * Este metodo es mas general que isMappedOnSameTableSubclass.
    */
   public static function isMappedOnSameTable( $class1, $class2 )
   {
      Logger::getInstance()->pm_log("PM::isMappedOnSameTable $class1, $class2");
      
      // TODO
      // el caso superclase subclase lo handlea isMappedOnSameTableSubclass.

      $table1 = YuppConventions::tableName( $class1 );
      $table2 = YuppConventions::tableName( $class2 );
      
      //Logger::getInstance()->log( "isMappedOnSameTable: table1 $table1" );
      //Logger::getInstance()->log( "isMappedOnSameTable: table2 $table2" );
      
      return ($table1 === $table2);
   }
   
} // PersistentManager

?>