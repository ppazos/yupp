<?php

// http://code.google.com/p/yupp/issues/detail?id=143
// YuppLoader::loadInterface( "core.persistent", "POLoader" );

class TableGen {

   /**
    * generateAll
    * Genera todas las tablas correspondientes al modelo previamente cargado.
    * 
    * @pre Deberia haber cargado, antes de llamar, todas las clases persistentes.
    */
   static public function generateAll()
   {
      Logger::getInstance()->pm_log("TableGen::generateAll ======");
      
      $yupp = new Yupp();
      $appNames = $yupp->getAppNames();
      
      foreach ($appNames as $appName)
      {
          $dalForApp = new DAL($appName); // No puedo usar this->dal porque esta configurada para 'core'
        
          // Todas las clases del primer nivel del modelo.
          $A = ModelUtils::getSubclassesOf( 'PersistentObject', $appName ); // FIXME> no es recursiva!
    
          // Se utiliza luego para generar FKs.
          $generatedPOs = array();
    
          foreach( $A as $clazz )
          {
             $struct = MultipleTableInheritanceSupport::getMultipleTableInheritanceStructureToGenerateModel( $clazz );
    
             // struct es un mapeo por clave las clases que generan una tabla y valor las clases que se mapean a esa tabla.
             foreach ($struct as $class => $subclassesOnSameTable)
             {
                // Instancia que genera tabla
                $c_ins = new $class(); // FIXME: supongo que ya tiene withTable, luego veo el caso que no se le ponga WT a la superclase...
                // FIXME: como tambien tiene los atributos de las superclases y como van en otra tabla, hay que sacarlos.
                
                // Para cara subclase que se mapea en la misma tabla
                foreach ( $subclassesOnSameTable as $subclass )
                {
                   $sc_ins = new $subclass(); // Para setear los atributos.
                   
                   $props = $sc_ins->getAttributeTypes();
                   $hone  = $sc_ins->getHasOne();
                   $hmany = $sc_ins->getHasMany();
                   
                   // FIXME: si el artibuto no es de una subclase parece que tambien pone nullable true...
                   
                   // Agrega constraint nullable true, para que los atributos de las subclases
                   // puedan ser nulos en la tabla, para que funcione bien el mapeo de herencia de una tabla.
                   //Logger::getInstance()->pm_log( "Para cada attr de: $subclass " . __FILE__ . " " . __LINE__);
                   foreach ($props as $attr => $type)
                   {
                      // FIXME: esta parte seria mas facil si simplemente cuando la clase tiene la constraint 
                      // y le seteo otra del mismo tipo para el mismo atributo, sobreescriba la anterior.
    
                      $constraint = $sc_ins->getConstraintOfClass( $attr, 'Nullable' );
                      if ($constraint !== NULL)
                      {
                         //Logger::getInstance()->log( "CONTRAINT NULLABLE EXISTE!");
                         // Si hay, setea en true
                         $constraint->setValue(true);
                      }
                      else
                      {
                         // Si no hay, agrega nueva
                         //Logger::getInstance()->log( "CONTRAINT NULLABLE NO EXISTE!, LA AGREGA");
                         $sc_ins->addConstraints($attr, array(Constraint::nullable(true)));
                      }
                   }
                   
                   //Logger::getInstance()->pm_log( "Termina con las constraints ======= " . __FILE__ . " " . __LINE__);
                   
                   // Se toma luego de modificar las restricciones
                   $constraints = $sc_ins->getConstraints();
                   
                   foreach( $props as $name => $type ) $c_ins->addAttribute($name, $type);
                   foreach( $hone  as $name => $type ) $c_ins->addHasOne($name, $type);
                   foreach( $hmany as $name => $type ) $c_ins->addHasMany($name, $type);
                   
                   // Agrego las constraints al final porque puedo referenciar atributos que todavia no fueron agregados.
                   foreach( $constraints as $attr => $constraintList ) $c_ins->addConstraints($attr, $constraintList);
                }
                
                $parent_class = get_parent_class($c_ins);
                if ( $parent_class !== 'PersistentObject' ) // Si la instancia no es de primer nivel
                {
                   // La superclase de c_ins se mapea en otra tabla, saco esos atributos...
                   $suc_ins = new $parent_class();
                   $c_ins = PersistentObject::less($c_ins, $suc_ins); // Saco los atributos de la superclase
                }
                
                $tableName = YuppConventions::tableName( $c_ins );

                // FIXME: esta operacion necesita instanciar una DAL por cada aplicacion.
                // La implementacion esta orientada a la clase, no a la aplicacion, hay que modificarla.
                
                // Si la tabla ya existe, no la crea.
                if ( !$dalForApp->tableExists( $tableName ) )
                {
                   // FIXME: c_ins no tiene las restricciones sobre los atributos inyectados.
                   self::generate( $c_ins, $dalForApp );
                
                   // Para luego generar FKs.
                   $generatedPOs[] = $c_ins;
                }
             } // foreach ($struct as $class => $subclassesOnSameTable)
          } // foreach( $A as $clazz )
          
          
          // ======================================================================
          // Crear FKs en la base.
          
          //Logger::struct( $generatedPOs, "GENERATED OBJS" );
          
          foreach ($generatedPOs as $ins)
          {
             $tableName = YuppConventions::tableName( $ins );
             $fks = array();
             
             // FKs hasOne
             $ho_attrs = $ins->getHasOne();
             foreach ( $ho_attrs as $attr => $refClass )
             {
                // Problema: pasa lo mismo que pasaba en YuppConventions.relTableName, esta tratando
                // de inyectar la FK en la tabla incorrecta porque la instancia es de una superclase
                // de la clase donde se declara la relacion HasOne, entonces hay que verificar si una
                // subclase no tiene ya el atributo hasOne declarado, para asegurarse que es de la
                // instancia actual y no intentar generar la FK si no lo es.
                
                $instConElAtributoHasOne = NULL;
                $subclasses = ModelUtils::getAllAncestorsOf( $ins->getClass() );
                
                foreach ( $subclasses as $aclass )
                {
                   $ains = new $aclass();
                   if ( $ains->hasOneOfThis( $refClass ) )
                   {
                      //Logger::getInstance()->log( $ains->getClass() . " TIENE UNO DE: $refClass" );
                      $instConElAtributoHasOne = $ains; // EL ATRIBUTO ES DE OTRA INSTANCIA!
                      break;
                   }
                }
                
                // Si el atributo de FK hasOne es de la instancia actual, se genera:
                if ( $instConElAtributoHasOne === NULL )
                {
                   // Para ChasOne esta generando "chasOne", y el nombre de la tabla que aparece en la tabla es "chasone".
                   $refTableName = YuppConventions::tableName( $refClass );
                   
                   $fks[] = array(
                             'name'    => DatabaseNormalization::simpleAssoc($attr), // nom_id, $attr = nom
                             'table'   => $refTableName,
                             'refName' => 'id' // Se que esta referencia es al atributo "id".
                            );
                }
             }
             
             // FKs tablas intermedias HasMany
             $hasMany = $ins->getHasMany();
             
             foreach ( $hasMany as $attr => $assocClassName )
             {
                //Logger::getInstance()->pm_log("AssocClassName: $assocClassName, attr: $attr");
                
                if ( $ins->isOwnerOf( $attr ) ) // VERIFY, FIXME, TODO: Toma la asuncion de que el belongsTo es por clase. Podria generar un problema si tengo dos atributos de la misma clase pero pertenezco a uno y no al otro porque el modelo es asi.
                {
                   $hm_fks = array();
                   $hasManyTableName = YuppConventions::relTableName( $ins, $attr, new $assocClassName() );
       
                   // "owner_id", "ref_id" son FKs.
       
                   // ===============================================================================
                   // El nombre de la tabla owner para la FK debe ser el de la clase 
                   // donde se declara el attr hasMany,
                   // no para el ultimo de la estructura de MTI (como pasaba antes).
                   $classes = ModelUtils::getAllAncestorsOf( $ins->getClass() );
          
                   //Logger::struct( $classes, "Superclases de " . $ins1->getClass() );
                   
                   $instConElAtributoHasMany = $ins; // En ppio pienso que la instancia es la que tiene el atributo masMany.
                   foreach ( $classes as $aclass )
                   {
                      $_ins = new $aclass();
                      if ( $_ins->hasManyOfThis( $assocClassName ) )
                      {
                         //Logger::getInstance()->log("TIENE MANY DE " . $ins2->getClass());
                         $instConElAtributoHasMany = $_ins;
                         break;
                      }
                      
                      //Logger::struct( $ins, "Instancia de $aclass" );
                   }
                   // ===============================================================================
                   
                   $hm_fks[] = array(
                             'name'    => 'owner_id',
                             'table'   => YuppConventions::tableName( $instConElAtributoHasMany->getClass() ), // FIXME: Genera link a gs (tabla de G1) aunque el atributo sea declarado en cs (tabla de C1). Esto puede generar problemas al cargar (NO PASA NADA AL CARGAR, ANDA FENOMENO!), aunque la instancia es la misma, deberia hacer la referencia a la tabla correspondiente a la instancia que declara el atributo, solo por consistencia y correctitud.
                             'refName' => 'id' // Se que esta referencia es al atributo 'id'.
                            );
       
                   $hm_fks[] = array(
                             'name'    => 'ref_id',
                             'table'   => YuppConventions::tableName( $assocClassName ),
                             'refName' => 'id' // Se que esta referencia es al atributo 'id'.
                            );
                            
                   // Genera FKs
                   $dalForApp->addForeignKeys($hasManyTableName, $hm_fks);
                }
             } // foreach hasMany
             
             // Genera FKs
             $dalForApp->addForeignKeys($tableName, $fks);
             
          } // foreach PO
      } // foreach app
   } // generateAll


   /**
    * generate
    * Genera la tabla para una clase y todas las tablas intermedias 
    * para sus relaciones hasMany de la que son suyas.
    * 
    * Si dalForApp es NULL se usa this->dal, de lo contrario se usa esa DAL.
    */
   static private function generate( $ins, $dalForApp = NULL )
   {
      Logger::getInstance()->pm_log("TableGen::generate");
      
      // La DAL que se va a usar
      //$dal = $this->dal;
      //if ($dalForApp !== NULL) $dal = $dalForApp;
      $dal = $dalForApp; // FIXME: creo que siempre se pasa dalForApp, no hay porque setear null por defecto.
      
      // TODO: Si la tabla existe deberia hacer un respaldo y borrarla y generarla de nuevo.
      //DROP TABLE IF EXISTS `acceso`;

      // Si la clase tiene un nombre de tabla, uso ese, si no el nombre de la clase.
      $tableName = YuppConventions::tableName( $ins );
      
      // Ya se sabe que id es el identificador de la tabla, es un atributo inyectado por PO.
      $pks = array (
               array (
                 'name'    => 'id',
                 'type'    => Datatypes :: INT_NUMBER,
                 'default' => 1
               )
             );
      
      /* EJEMPLO de la estructura que se debe crear.
      $cols = array(
                     array('name'     => 'name',
                           'type'     => Datatypes :: TEXT,
                           'nullable' => false),
                     // FK
                     array('name'     => 'ent_id',
                           'type'     => Datatypes :: INT_NUMBER,
                           'nullable' => true)
                   );
      */
      
      // =====================================================================================================
//      $nullable = NULL; // Hay que determinar si el atributo es nullable.
      
      // Si es una clase de nivel 2 o superior y esta mapeado en la misma tabla que su superclase, 
      // todos sus atributos (declarados en ella) deben ser nullables.
      // TODO: ahora no tengo una funcionalidad que me diga que atributos estan declarados en que
      // clase, por ahora le pongo que todos sus atributos sean nullables.
      
      // =====================================================================================================
      // FIXME: no sirve chekear por la clase porque la instancia que me pasan es un merge de todas las 
      // subclases que se mapean en la misma tabla, asi que puede ser que parent_class sea POe igual 
      // tenga que declarar nullables.
      
      // >> Solucion rapida <<, para los atributos de las subclases, en generateAll inyectarles
      //                         contraints nullables true.
      
      // Son iguales, no se sobreescribe el valor de "class" por el de la instancia real porque no interesa, 
      // solo son instancias de merges de POs para una tabla.
      //Logger::getInstance()->log( "getClass: " . $ins->getClass() );
      //Logger::getInstance()->log( "GET_CLASS: " . get_class($ins) );
      
//      if ( get_parent_class($ins) != PersistentObject && 
//           self::isMappedOnSameTable($ins->getClass(), get_parent_class($ins)) )
//      {
//         $nullable = true;
//      }
      // =====================================================================================================
      
      $cols  = array();
      $attrs = $ins->getAttributeTypes(); // Ya tiene los MTI attrs!
      foreach ( $attrs as $attr => $type )
      {
         if ( $attr !== 'id' )
         {
            $cols[] = array(
                        'name' => $attr,
                        'type' => $type,
                        'nullable' => (DatabaseNormalization::isSimpleAssocName( $attr )) ? true : $ins->nullable( $attr ) // FIXME: si es un atributo de una subclase (nivel 2 o mas, deberia ser nullable independientemente de la restriccion nullable).
                      );
         }
      }
      
      // ====================================================================================================
      // Sigue fallando, genera esto: (el vacio en nullable es el false)
      //  [5] => Array
      //  (
      //      [name] => entrada_id
      //      [type] => type_int32
      //      [nullable] => 
      //  )
      
      // Mientras que tengo esto en el objeto: (o sea la constraint nullable esta en true)
      //          [entrada_id] => Array
      //          (
      //              [0] => Nullable Object
      //                  (
      //                      [nullable:private] => 1
      //                  )
      //          )
      
      // El problema es que PO.nullable cuando es un atributo de referencia hasOne, 
      // se va a fijar si el atributo hasOne es nullable, y en este caso el atributo 
      // NO es nullable, lo que hace a la referencia no nullable.
      // SOLUCION!: Lo resuelvo fijandome si es un atributo de referencia, lo hago 
      // nullable, si no me fijo en si es nullable en el PO.
      
      // =========================================================
      //Logger::struct( $cols, "=== COLS ===" );

      $dal->createTable2($tableName, $pks, $cols, $ins->getConstraints());      

      // Crea tablas intermedias para las relaciones hasMany.
      // Estas tablas deberan ser creadas por las partes que no tienen el belongsTo, o sea la clase duenia de la relacion.
      // FIXME: si la relacion hasMany esta declarada en una superClase, la clase actual tiene la 
      //        relacion pero no deberia generar la tabla de JOIN a partir de ella, si no de la 
      //        tabla en la que se declara la relacion.
      $hasMany = $ins->getHasMany();
      foreach ( $hasMany as $attr => $assocClassName )
      {
         //Logger::getInstance()->pm_log("AssocClassName: $assocClassName, attr: $attr");
         
         //if ($ins->isOwnerOf( $attr )) Logger::show("isOwner: $attr", "h3");
         //if ($ins->attributeDeclaredOnThisClass( $attr )) Logger::show("attributeDeclaredOnThisClass: $attr", "h3");
         
         // VERIFY, FIXME, TODO: Toma la asuncion de que el belongsTo es por clase.
         // Podria generar un problema si tengo dos atributos de la misma clase pero
         // pertenezco a uno y no al otro porque el modelo es asi.
         
         // Para casos donde no es n-n el hasMany, lo que importa es donde se declara la relacion,
         // no que lado es el owner. Para la n-n si es importante el owner.
         
         // Verifico si la relacion es hasMany n-n
         if ( $ins->getClass() !== $assocClassName ) // Verifico que no tenga un hasMany hacia mi mismo. Si tengo una relacion hasMany con migo, al verificar si es n-n siempre da true (porque verifica un bucle).
         {
            $hmRelObj = new $assocClassName(NULL, true);
            if ( $hmRelObj->hasManyOfThis($ins->getClass()) )
            {
               if ( $ins->isOwnerOf( $attr ) )
               {
                  self::generateHasManyJoinTable($ins, $attr, $assocClassName, $dal);
               }
            }
            else if ( $ins->attributeDeclaredOnThisClass( $attr ) ) // Para generar la tabla de JOIN debo tener al atributo declarado en mi.
            {
               self::generateHasManyJoinTable($ins, $attr, $assocClassName, $dal);
            }
         } // si el hasMany no es con migo mismo.
         else if ( $ins->attributeDeclaredOnThisClass( $attr ) ) // Para generar la tabla de JOIN debo tener al atributo declarado en mi.
         {
            self::generateHasManyJoinTable($ins, $attr, $assocClassName, $dal);          
         }
      }
      
   } // generate
   
   static private function generateHasManyJoinTable($ins, $attr, $assocClassName, $dal)
   {
      Logger::getInstance()->pm_log("TableGen::generateHasManyJoinTable");
      
      $tableName = YuppConventions::relTableName( $ins, $attr, new $assocClassName() );

      //Logger::struct($this->getDataFromObject( new ObjectReference() ), "ObjRef ===");
      
      // "owner_id", "ref_id" son FKs.
      // Aqui se generan las columnas, luego se insertan las FKs
      // =========================================================

      $pks = array(
               array(
                 'name'    => 'id',
                 'type'    => Datatypes :: INT_NUMBER,
                 'default' => 1
               )
             );

      $cols = array();

      // FIXME: todo lo declarado aqui esta declarado en la clase ObjectReference, 
      //        deberia hacerse referencia a eso en lugar de redeclarar todo 
      //        (como los atributos y restricciones).
      
      $cols[] = array(
                 'name' => "owner_id",
                 'type' => Datatypes::INT_NUMBER, // Se de que tipo, esta definido asien ObjectReference.
                 'nullable' => false );
      $cols[] = array(
                 'name' => "ref_id",
                 'type' => Datatypes :: INT_NUMBER, // Se de que tipo, esta definido asien ObjectReference.
                 'nullable' => false );
      $cols[] = array(
                 'name' => "type",
                 'type' => Datatypes :: INT_NUMBER, // Se de que tipo, esta definido asien ObjectReference.
                 'nullable' => false );
       $cols[] = array(
                 'name' => "deleted",
                 'type' => Datatypes :: BOOLEAN, // Se de que tipo, esta definido asien PO.
                 'nullable' => false );
       $cols[] = array(
                 'name' => "class",
                 'type' => Datatypes :: TEXT, // Se de que tipo, esta definido asien PO.
                 'nullable' => false );
                      
       // El tema con la columna ord es que igual esta declarada en la clase ObjectReference,
       // entonces las consultas que se basen en los atributos que tenga la clase van a hacer
       // referencia a "ord" aunque la coleccion hasMany no sea una lista. 
       // Entonces lo que hago es generar igual la columna ord aunque la coleccion no sea lista,
       // y queda nullable, asi si es SET o COLLECTION no se da bola a ord.
       $cols[] = array(
                 'name' => "ord",
                 'type' => Datatypes :: INT_NUMBER, // Se de que tipo, esta definido asien PO.
                 'nullable' => true );
         
      // Si es una lista se genera la columna "ord".
      /*
      $hmattrType = $ins->getHasManyType( $attr );
      if ( $hmattrType === PersistentObject::HASMANY_LIST )
      {
         $cols[] = array(
                 'name' => "ord",
                 'type' => Datatypes :: INT_NUMBER, // Se de que tipo, esta definido asien PO.
                 'nullable' => true
                );
      }
      */
  
      $dal->createTable2( $tableName, $pks, $cols, array() );

   } // generateHasManyJoinTable
}

?>