<?php

// USada por DAL para normalizar nombres.
// FIXME: deberia estar en su propio archivo, talvez en /config, o hacer un dir /conventions y pornerla ahi con YuppConventions.
class DatabaseNormalization {

   public static function table( $tableName )
   {
      return String::toUnderscore( $tableName );
   }

   public static function col( $colName )
   {
      return strtolower($colName);
   }

   public static function simpleAssoc( $colName )
   {
      return $colName . "_id";
   }

   // Para saber si el nombre de una columna es una asociacion con otra tabla (FK)
   public static function isSimpleAssocName( $colName )
   {
      $largo = strlen($colName);
      $largo_suffix = strlen("_id");
      $suffix = substr($colName, $largo-$largo_suffix, $largo );
      return (strcmp( $suffix, "_id" ) == 0);
   }

   // Entra algo de la forma "email_id" y sale "email".
   public static function getSimpleAssocName( $colName )
   {
      $largo = strlen($colName);
      $largo_suffix = strlen("_id");
      $prefix = substr($colName, 0, $largo-$largo_suffix );
      return $prefix;
   }
}

// Clase auxiliar que ofrece una interfaz de consultas de alto nivel para ser utilizadas desde el
// PersistentManager y sobre el conector al DBMS configurado, por ahora DatabaseMySQL.
// Es quien crea las queries.

// RESPONSABILIDADES:
// - Normalizar nombres.
// - Generar SQL.
// - Resolver que DBMS se usa.

class DAL {

   private $db;

   // TODO: POR AHORA LOS DATOS PARA ACCEDER A LA BD SE CONFIGURAR AQUI...
   private $appName; // Aplicacion para la que se configura la DAL
   private $url;
   private $user;
   private $pass;
   private $database;

   /*
   private static $instance = NULL;

   public static function getInstance($appName)
   {
      if (!self::$instance) self::$instance = new DAL($appName);
      return self::$instance;
   }
   */

   /**
    * @param String appName
    * @param Array datasource puede servir para usar distintos datasources para distintas pruebas mas alla de los 3 modos de ejecucion. 
    */
   public function __construct($appName, $datasource = NULL)
   {
      Logger::getInstance()->log("DAL::construct");
      
      // ===============================================
      $cfg = YuppConfig::getInstance();
      
      // TODO: pasarle el nombre de la app actual.
      // FIXME: esto no funciona si la app es "core",
      //        y trabajo con distintas apps, por ejemplo
      //        al generar todas las tablas en las dbs.
      //        Necesito pasarle como parametro al constructor
      //        de DAL el nombre de la app para la cual quiero
      //        el datasource, y que sea PM el que obtenga el
      //        appName correcto, sea del contexto o porque genere
      //        las tablas para una app particular.
      //$ctx = YuppContext::getInstance();
      //$appName = $ctx->getApp();
      
      $this->appName = $appName;
      
//      Logger::getInstance()->on();
//      Logger::getInstance()->log("DAL __construct appName: $appName");
//      Logger::getInstance()->off();
      
      if ($datasource == NULL)
         $datasource = $cfg->getDatasource($appName);
      
      // FIXME: Esto es solo para mysql y postgres =====
      $this->url      = $datasource['url'];
      $this->user     = $datasource['user'];
      $this->pass     = $datasource['pass'];
      $this->database = $datasource['database'];
      // ===============================================
      
      // Constructor por configuracion del dbms
      // OBS: cada vez que agregue un soporte nuevo tengo que agregar la opcion al switch.
      
      // FIXME: que la configuracion use directamente los nombres de las clases de DB para ahorrar el switch.
      
      // TODO: deberia tener una fabrica con esto adentro, y la fabrica tal vez deberia cargar
      // las clases automaticamente en lugar de ir agregando cada tipo de conector en el switch.
      //switch( $cfg->getDatabaseType() )
      switch( $datasource['type'] )
      {
         case YuppConfig::DB_MYSQL:
            YuppLoader::load( "core.db", "DatabaseMySQL" );
            $this->db = new DatabaseMySQL();
         break;
         case YuppConfig::DB_SQLITE:
            YuppLoader::load( "core.db", "DatabaseSQLite" );
            $this->db = new DatabaseSQLite();
         break;
         case YuppConfig::DB_POSTGRES:
            YuppLoader::load( "core.db", "DatabasePostgreSQL" );
            $this->db = new DatabasePostgreSQL();
         break;
		 case YuppConfig::DB_SQLSRV:
            YuppLoader::load( "core.db", "DatabaseSQLServer" );
            $this->db = new DatabaseSQLServer();
         break;
         default:
            throw new Exception('datasource type no soportado: '.$datasource['type']);
      }
      
      // TODO: que dmbs desde config, perfecto para factory pattern.
      $this->db->connect( $this->url, $this->user, $this->pass, $this->database ); // TODO: POR AHORA LOS DATOS PARA ACCEDER A LA BD SE CONFIGURAR AQUI...
   }

   public function __destruct()
   {
      Logger::getInstance()->log("DAL::destruct ". $this->appName);
      $this->db->disconnect();
   }

   // Ejecuta una consulta y devuelve el resultado como una matriz asociativa.
   public function query( Query $query )
   {
      return $this->sqlQuery( $this->db->evaluateQuery( $query ) );
   }
   
   /**
    * Ejecuta queries de sql.
    */
   public function sqlQuery($q)
   {
      $res = array();
      try
      {
         if ( !$this->db->query( $q ) ) throw new Exception("ERROR");

         //echo "RES SIZE: " . $this->db->resultCount() . "<br/>";

         while ( $row = $this->db->nextRow() ) $res[] = $row;
      }
      catch (Exception $e)
      {
         echo $e->getMessage();
         echo $this->db->getLastError(); // DBSTD
      }

      return $res;
   }
   
   /**
    * Ejecuta inserts o updates de sql.
    */
   public function sqlExecute($q)
   {
      $this->db->execute( $q );
   }


   // http://code.google.com/p/yupp/issues/detail?id=123
   // Si recibiera la app podria verificar si existe o no la db, esto lo hace ahora CoreController.createDb que es desde el unico lugar que se invoca este metodo.
   public function createDatabase($dbname)
   {
      $this->db->createDatabase($dbname);
   }


   // FIXME: depende del DBMS
   /**
    * createTable
    * Crea una nueva tabla con la informacion que se le pasa.
    * 
    * @param $tableName nombre de la tabla a crear.
    * 
    * @param $pks       claves primarias de la tabla. Array de arrays, cada array interno 
    *                   tiene claves: requeridas(name(string),type(string)), opcionales(default(any)),
    *                   el valor de "type" debe ser alguno de los tipos que se utilizan para definir 
    *                   campos en las clases del modelo. "name" es el nombre de la columna en la tabla,
    *                   default es el valor por defecto para esa columna.
    * 
    * @param $cols      columnas a generar. Array de arrays, cada array interno 
    *                   tiene claves: requeridas(name(string),type(string)), opcionales(default(any),nullable(boolean))
    *                   "nullable" es un booleano que indica si la columna puede tener valor NULL.
    * 
    * @param $constraints array de restricciones para cada nombre de columna, para cada nombre de columna 
    *        hay un array de restricciones y pueden haber columnas sin restricciones.
    */
   // SQLite> CREATE TABLE ggg (id int, name CHAR(255), email CHAR(255), PRIMARY KEY (id));
   // MySQL> aca le pongo ` y funciona, pero en la doc de la web no le pone, y esas comillas hacen q no me ande el lite.
   public function createTable2($tableName, $pks, $cols, $constraints)
   {
      Logger::getInstance()->dal_log("DAL::createTable2 : " . $tableName);
      
      $q_ini = 'CREATE TABLE ' . $tableName . ' (';
      $q_end = ')';
      
      // Keys obligatorias: name, type.
      // Keys opcionales: default.
      
      $q_pks = "";
      //$q_pks = "PRIMARY KEY ( id )";
      foreach ( $pks as $pk )
      {
         // =============================================================================================================
         // FIXME: arreglo rapido porque no hay constraints para id, ver el sig. FIXME en PersistentManager en linea 2203
         // FIXME: c_ins no tiene las restricciones sobre los atributos inyectados.
         $constraintsOrNull = (isset($constraints[$pk['name']])) ? $constraints[$pk['name']] : NULL;
         $q_pks .= $pk['name'] . " " . 
                   $this->db->getDBType($pk['type'], $constraintsOrNull ) . " " .
                   ((array_key_exists('default', $pk)) ? "DEFAULT " . $pk['default'] : '') . // si hay default lo pone 
                   " PRIMARY KEY, "; // TODO!
      }
      
      // Keys obligatorias: name, type.
      // Keys opcionales: default, nullable.
      $q_cols = "";
      foreach ( $cols as $col )
      {
         // FIXME: arreglo rapido porque no hay constraints para id, ver el sig. FIXME en PersistentManager en linea 2203
         // FIXME: c_ins no tiene las restricciones sobre los atributos inyectados.
         $constraintsOrNull = (isset($constraints[$col['name']])) ? $constraints[$col['name']] : NULL;
         $q_cols .= $col['name'] . " " . 
                    $this->db->getDBType($col['type'], $constraintsOrNull ) . " " .
                    ((array_key_exists('default', $col)) ? "DEFAULT " . $col['default'] : '') . // si hay default lo pone 
                    ((array_key_exists('nullable', $col) && $col['nullable']) ? " NULL" : " NOT NULL") . // Si la clave nullable esta y si el ooleano en nullable es true, pone NULL.
                    ", ";
      }
      
      
      // Keys obligatorias: name, type, table, refName.
      $q = $q_ini . $q_pks . substr($q_cols,0,-2) . $q_end; // substr para sacar ", " del final.

      // FIXME: para que funcione la transaccionalidad, el motor de la DB en MySQL debe ser InnoDB
      // hay que agregar esto a la consulta: ENGINE = InnoDB; al final
      // http://dev.mysql.com/doc/refman/5.1/en/create-table.html
      // Agrega opciones sobre el charset y el collate
      $q .= $this->db->tableOptions();


      //Si hay una excepcion, va a la capa superior.
      $this->db->execute( $q );
      
   } // createTable2
   
   /**
    * addForeignKeys
    * Se llama luego de crear todas las tablas, sirve para agregar las FKs de una tabla a otras.
    * 
    * @param $tableName nombre de la tabla a agregarle las fks.
    * 
    * @param $fks       claves externas a otras tablas. Array de arrays, cada array interno 
    *                   tiene claves: requeridas(name(string), type(string), table(string), refName(string)),
    *                   "table" es la tabla referenciada por la FK y "refName" es la columna referenciada por la FK.
    */
   public function addForeignKeys($tableName, $fks, $isHasMany = true)
   {
      // Lo resuelve cada DBMS particular.
      $this->db->addForeignKeys($tableName, $fks, $isHasMany);  
   }


   // Modifica un registro ya existente. DEBE tener el id seteado en los values.
   // FIXME: si la tabla se deriva del objeto no veo la necesidad de pasarle ambos, 
   // anque podria ser necesario para las tablas intermedias de relaciones 1-* y *-*
   // FIXME: no deberia pasarle obj, deberia ser un array de valores.
   public function update ( $tableName, $data )
   {
      Logger::getInstance()->dal_log("DAL::update " . $tableName . " " . $data['class']);

      // FIXME: esto se deberia hacer en PM y en DAL no se deberian manejar
      //        ni POs ni temas de consistencia entre instancias parciales.
      // ===================================================================

      $this->update_query2( $data, $tableName );
   }

   /**
    * @param $tableName nombre de la tabla donde buscar el objeto con identificador $id.
    * @param $id identificador del objeto buscado.
    * @return true si el elemento con identificador $id existe en la tabla $tableName.
    */
   public function exists( $tableName, $id )
   {
      Logger::getInstance()->dal_log("DAL::exists " . $tableName . " (" . $id . ")");

      if ( empty($id) ) return false;

      $q = "SELECT COUNT(id) as count FROM $tableName WHERE id=$id";
      $this->db->query( $q );
      $row = $this->db->nextRow();

      return $row['count'] == 1;
   }


   // Obs: no puedo generar cosas con id = 0 si no da problemas porque se confunde con NULL.
   public function get( $tableName, $id )
   {
      if ( $id === NULL ) throw new Exception("DAL.get: id no puede ser null");

      $this->db->query( "SELECT * FROM " . $tableName . " WHERE id=" . $id );

      if ( $row = $this->db->nextRow() )
      {
         return $row;
      }

      // TODO: deberia exceptuar o retornar null?
      //throw new Exception("DAL.get: no se encuentra el objeto con id ". $id . " en la tabla " . $tableName);
      // Retorno un array vacio porque se espera que retorne un array.
      // Luego el PM se encarga de devolver NULL si no existe el objeto con id $id.
      // Sino se hace esto, la except puede llegar hasta el usuario final... y el programador deberia hacer catch de cada get, es mas natural ver si el objeto es null en lugar de tener catchs por todos lados.
      // http://code.google.com/p/yupp/issues/detail?id=132
      
      return array();
   }


   /**
    * Lista todos los elementos de una tabla dadas ciertas restricciones como:
    * - max: cantidad maxima de filas devueltas.
    * - offset: desfasaje desde el primer registro de la tabla.
    * - where: condiciones sobre los valores de las columnas de la tabla. Es una instancia de Condition.
	* PO.filtrarParams se encarga de que siempre venga un max y offset validos, ademas de sort(atributo) y dir(asc/desc)
    */
   public function listAll( $tableName, ArrayObject $params )
   {
      Logger::getInstance()->dal_log('DAL::listAll ' . $tableName);
      
      // TODO: EN PARAMS PDRIA PASAR CONDICIONES SOBRE ATRIBUTOS, PARA HACER BUSQUEDAS QUE ACEPTEN 'WHERE', esto lo necesito
      //       para soportar herencia, ya q el listAll debe traer solo instancias de clases de la estructura de herencia
      //       de la clase que necesito (o deberia pasarle tambien la clase que nenecito ??? )
      $limit = '';
      $orderBy = '';

      if ($params === NULL ) throw new Exception('DAL.getAll: params es null');

	  // =======================================================================
	  // FIXME:
	  // LIMIT funciona en MySQL, PostgreSQL, SQLite, no en SQLSERVER.
	  // =======================================================================
	  
	  // Si es SQLServer la consulta con LIMIT se arma distinta y con TOP
	  // - http://stackoverflow.com/questions/971964/limit-10-20-in-sqlserver
	  // - LIMIT 10 ~ SELECT TOP 10 * FROM stuff;
	  if ($this->db instanceof DatabaseSQLServer)
	  {
	     // Siempre viene max y offset, PO.filterParams lo asegura
         $max = $params['max'];	
         $offset = $params['offset'];
         $orderBy = ' ORDER BY '. $params['sort'] .' '. $params['dir'];
		 
         // FIXME: los filtros que no sean por rowNum DEBEN estar en la query interna.
       
		   // La consulta interna es para hacer paginacion
		   // WHERE: Las condiciones donde dice tableName pone T2 (no puede evaluar condiciones sobre atributos de tablas no mencionados en el FROM)
		   //  - http://social.msdn.microsoft.com/Forums/sqlserver/en-US/3b2e0875-e98c-4931-bcb4-e9f449b637d7/the-multipart-identifier-aliasfield-could-not-be-bound
		   
         
         // Puedo hacer SELECT * porque hay un solo FROM
         $q = 'SELECT * FROM ( '.
               'SELECT ROW_NUMBER() OVER (ORDER BY id) AS rowNum, * FROM '. $tableName .' WHERE '. $this->db->evaluateAnyCondition( $params['where'] ) .
              ' ) AS T2 '.
              'WHERE T2.rowNum-1 >= '. $offset .' AND T2.rowNum-1 < '.($offset+$max);
         
         if (isset($params['sort']) || array_key_exists('sort', $params)) // && $params['sort'])
         {
           $q .= ' ORDER BY '. $params['sort'] .' '. $params['dir'];
         }
	  }
	  else // Arma consulta para MySQL, PostgreSQL y SQLite
	  {
         // No puede tener offset sin limit! se chekea arriba.
         // Si viene max siempre viene offset, se chekea arriba.
         if (isset($params['max']) || array_key_exists('max', $params))
         {
            $limit = ' LIMIT ' . $params['max'];
            if (isset($params['offset']) || array_key_exists('offset', $params)) $limit .= ' OFFSET ' . $params['offset'];
         }

         if (isset($params['sort']) || array_key_exists('sort', $params)) // && $params['sort'])
         {
           $orderBy = ' ORDER BY '. $params['sort'] .' '. $params['dir'];
         }

         // Where siempre viene porque en PM se inyecta las condicioens sobre las subclases (soporte de herencia)
         $q = 'SELECT * FROM ' . $tableName . ' WHERE ' .
              $this->db->evaluateAnyCondition( $params['where'] ) .
              $orderBy . $limit;
      }
	  
      $this->db->query( $q );

      // TODO: Como hago para devolver un array de objetos ya creados...
      // SOL: devuelvo los datos, arriba en persistent object se crean los objetos.

      // FIXME: Si pudiera tener una referencia directa a la estructura que devuelve los datos no tendria que hacer este bucle.
      $res = array();
      while ( $row = $this->db->nextRow() )
      {
          $res[] = $row; // Row es un array asociativo por los nombres de los atributos (columnas).
      }
      return $res;
      
   } // listAll

   /**
    * Elimina un solo registro, correspondiente con la clase e id.
    * No considera instancias parciales, de eso se encarga PM.
    * 
    * @param string $class clase del registro a eliminar.
    * @param int $id identificador del registro.
    * @param boolean $logical indica si la eliminacion es logica (true) o fisica (false).
    * 
    * @todo Si es MTI se que se va a llamar varias veces seguidas, porque no dejar que 
    *       las consultas se acumulen en un buffer (string) y luego se ejecuten todas 
    *       juntas, es mas, podria rodear con BEGIN y COMMIT para hacerla transaccional.
    * 
    * @todo No le deberia pasar class, deberia pasarle solo la tabla, DAL no debe saber de PO.
    */
   public function delete( $class, $id, $logical )
   {
      Logger::add( Logger::LEVEL_DAL, "DAL::delete " . __LINE__ );
      
      if ( $class === NULL ) throw new Exception("DAL.delete: class no puede ser null");
      if ( $id    === NULL ) throw new Exception("DAL.delete: id no puede ser null");
      
      $tableName = YuppConventions::tableName( $class );
      
      $this->deleteFromTable( $tableName, $id, $logical );
   }


   /**
    * Elimina un registro de una tabla. Puede ser eliminacion logica o fisica.
    */
   public function deleteFromTable( $tableName, $id, $logical )
   {
      if ($logical)
      {
         Logger::add( Logger::LEVEL_DAL, "DAL::delete LOGICAL " . __LINE__ );
         
         $data = array('id'=>$id, 'deleted'=>true); // No hay que actualizar atributo "class".
         $this->update_query2( $data, $tableName );
      }
      else
      {
         Logger::add( Logger::LEVEL_DAL, "DAL::delete FISICAL " . __LINE__ );
         
         $q = "DELETE FROM " . $tableName . " WHERE id=" . $id;
         $this->db->execute( $q );
      }
   } // deleteFromTable
   
   
   /**
    * Transaccionalidad.
    */
   public function withTransaction()
   {
      $this->db->withTransaction();
   }
   
   public function commitTransaction()
   {
      $this->db->commitTransaction();
   }
   
   public function rollbackTransaction()
   {
      $this->db->rollbackTransaction();
   }
   
   // TODO: un exists que reciba un queryBuilder, seria algo como existsWhere...

   //public function insert($tableName, &$obj)
   public function insert($tableName, $obj)
   {
      // FIXME: obj deberia ser una matriz de valores, no un PO. A DAL no deverian llegar POs.
      //        Y todas las operaciones sobre el PO deberian hacerse tambien en PM.
      Logger::getInstance()->dal_log("DAL::insert ". $obj->getClass() ." in table=$tableName");

      // =======================================================================
      // FIXME: deberia estar en PM
      // Soporte para multiple table inheritance mapping.
      // Si el objeto no tiene mti simplemente se devuelve un array con el mismo objeto de entrada.
      // El primero es siempre el que corresponde con la superclase de nivel 1
      $pinss = MultipleTableInheritanceSupport::getPartialInstancesToSave( $obj );
      
      //Logger::getInstance()->dal_log("insert_query count MTI ". count($pinss) . " " . __FILE__ . " " . __LINE__ );
      //Logger::struct( $pinss );
      
      // ======================================================================================================
      // http://code.google.com/p/yupp/issues/detail?id=111
      // New: todas las subclases en MTI tienen el mismo identificador que la superclase de nivel 1,
      //      asi hay que generar un solo identificador, ahorrando multiples consultas.
    
      // Necesito la tabla para la superclase, no la del objeto ($tableName)
      // Si viene un ObjectReference, no resuelve bien su nombre de tabla porque es calculado, entonces dejo el tableName que viene.
      $superTableName = $tableName;
      if ($obj->getClass() != 'ObjectReference')
         $superTableName = YuppConventions::tableName( $pinss[0] );
         
      $id = $this->generateNewId($superTableName); // Pide sobre la tableName de la superclase
      $obj->setId( $id );
      
      if ( count($pinss) == 1 ) // si no es mti, salva el caso de ObjectReference.
      {
         //Logger::struct( $pinss, "DAL.insert 1 ($tableName)" ); // OBS: si obj es comentario, pinss tiene un objeto que es Entrada, no Comentario.
         //Logger::struct( $obj, "DAL.insert 1 ($tableName)" );
         
         // Ahora inserta...
         //Logger::getInstance()->dal_log("insert_query call " . __FILE__ . " " . __LINE__ );
         $this->insert_query( $obj, $tableName );
      }
      else
      {
         //Logger::struct( $pinss, "DAL.insert 2 ($tableName)" );
         // ESTO SE DEBERIA HACER EN PM!

         // Procesa el modelo, arma instancias parciales, setea ids...
         foreach ( $pinss as &$partialInstance )
         {
            // Como tableName es el nombre de la tabla del objeto que quiero salvar, 
            // y posiblemente alguna superclase de obj se guarde en otra tabla,
            // para esas instancias parciales tengo que generar el nombre de la tabla.
            // Aunque para la tambla del objeto tengo el nombre, lo genero de nuevo para simplificar logica, de todos modos es el mismo...
            
            // Esta tabla no se usa! abajo en insert_query se saca el nombre de la tabla del propio objeto
            //$tableName = YuppConventions::tableName( $partialInstance );
            
            // Todas las instancias parciales usan el mismo id
            $partialInstance->setId( $id );
      
            // Seteo la clase real en cada una de las instancias parciales, para poder cargar (get, list, find) desde una instancia parcial.
            $partialInstance->setClass( $obj->getClass() );
            
         } // foreach
         
         foreach ( $pinss as &$partialInstance )
         {
            //Logger::getInstance()->dal_log("insert_query MTI call " . __FILE__ . " " . __LINE__ );
            $this->insert_query( $partialInstance ); // Saca la tabla del objeto, por eso no se la paso
            
         } // foreach
      } // si es mti

      Logger::getInstance()->dal_log("/DAL::insert");
      return $obj->getId(); // Devuelvo el id generado...
      
   } // insert


   /**
    * Clave'id' son obligatorias en $data.
    */
   private function update_query2( $data, $tableName )
   {
      // UPDATE hello_world_persona SET nombre='aaaa' ,edad='24' ,class='Persona' ,deleted='0' WHERE id=2
      if ( $data['id'] === NULL) throw new Exception("Clave 'id' es vacia...");
      if ( $tableName  === NULL) throw new Exception("Tablename es vacio...");
      
      //if (!$tableName) $tableName = YuppConventions::tableName( $data['class'] );

      $q = "UPDATE " . $tableName . " SET "; // DBSTD
      $tableAttrs = "";
      foreach ( $data as $attr => $value )
      {
         if ( strcmp($attr, "id") != 0 ) // No updateo el id...
         {
            $tableAttrs .= DatabaseNormalization::col( $attr ) ."=";
            if ( $value === NULL ) $tableAttrs .= "NULL ,"; // Si no se pone esto ponia '' y se guardaba 0, mientras necesito que se guarde NULL.
            else if ( is_string($value) ) $tableAttrs .= "'". addslashes($value) ."' ,"; // Debe agregar slashes solo si el valor es string, esto es por si guardo "'" dentro del propio string donde mysql me da error.
            else if ( is_bool($value) ) $tableAttrs .= "'". (($value===true)?"1":"0")  ."' ,"; // Pone '1' si es true, '0' si no.
            else $tableAttrs .= "'". $value ."' ,"; // FIXME: Ver si el value es literal...
         }
      }
      $tableAttrs = substr($tableAttrs, 0, sizeof($tableAttrs)-2);
      $q .= $tableAttrs;
      $q .= " WHERE id=" . $data['id'];

      // Si hay una excepcion, llega hasta la capa superior.
      $this->db->execute( $q );
      
   } // update_query2

   /**
    * Se recibe un objecto a la que ya se ha verificado que debe insertarse en la base de datos.
    * @param $object POs a salvar.
    */
   private function insert_query( $object, $tableName = NULL )
   {
      Logger::getInstance()->dal_log("DAL:insert_query " . __FILE__ . " " . __LINE__ );
      
      // INSERT INTO hello_world_persona ( nombre ,edad ,class ,id ,deleted ) VALUES ('pepe' ,'12' ,'Persona' ,'6' ,'' );
      if (!$tableName) $tableName = YuppConventions::tableName( $object );
      
      $q = "INSERT INTO " . $tableName . " ( "; // DBSTD
      $attrs = $object->getAttributeTypes(); // Recorro todos los atributos simples...
      $tableAttrs = "";
      foreach ( $attrs as $attr => $type )
      {
         $tableAttrs .= DatabaseNormalization::col( $attr ) ." ,"; // DBSTD
      }
      $tableAttrs = substr($tableAttrs, 0, sizeof($tableAttrs)-2);
      $q .= $tableAttrs;
      $q .= ") VALUES (";

      // El codigo es distinto al de update porque la forma de la consulta es distinta.
      // TODO: Si el valor es null tengo que poner null en la tabla, no el string vacio.
      // TODO: Verificar atributos no nullables en null en la instancia de la clase, esto falta agregar cosas a la clase persistente, "las restricciones"
      $tableVals = "";
      foreach ( $attrs as $attr => $type )
      {
         $value = $object->aGet( $attr ); // Valor del atributo simple.
         if ( $value === NULL ) $tableVals .= "NULL ,";
         else if ( is_string($value) ) $tableVals .= "'". addslashes($value) ."' ,"; // Debe agregar slashes solo si el valor es string, esto es por si guardo "'" dentro del propio string donde mysql me da error.
         else if ( is_bool($value) ) $tableVals .= "'". (($value===true)?"1":"0")  ."' ,"; // Pone '1' si es true, '0' si no.
         else $tableVals .= "'". $value ."' ,"; // FIXME: OJO, si no es literal no deberia poner comillas !!!!  y si es null deberia guardar null
         
         //echo $attr . " tiene tipo: " . gettype($value) . " y valor '" . $value . "'<br/>";
      }
      $tableVals = substr($tableVals, 0, sizeof($tableVals)-2);
      $q .= $tableVals;
      $q .= ");";

      // Si hay una excepcion, llega hasta la capa superior.
      $this->db->execute( $q );

   } // insert_query


// FIXME: depende del DBMS!!!!!!!!!!!
//        le hice correcciones para postgres, pero en mysql va a andar mal...
   public function count( $tableName, $params = array() )
   {
      Logger::getInstance()->dal_log("DAL::count $tableName");
      
      //return $this->db->count( $tableName,$params );
      
      $q = "SELECT count(id) as cant FROM " . $tableName;
      if (isset($params['where']))
      {
         if ($this->db instanceof DatabaseSQLServer)
	      {
            $q .= " WHERE " . $this->db->evaluateAnyCondition( $params['where'], new ArrayObject() );
         }
         else
         {
            $q .= " WHERE " . $this->db->evaluateAnyCondition( $params['where'] );
         }
      }

      $this->db->query( $q );
      $row = $this->db->nextRow();
      return $row['cant']; // dice que no existe el indice 'cant' aunque consulte con count(id) as cant
   }

// FIXME: depende del DBMS
// para postgres la primer consulta da NULL porque no hay items,
// ahi habria que decirle que no sume, que el resultado es 1 derecho.
   public function generateNewId ( $tableName )
   {
      //Logger::getInstance()->dal_log("DAL::generateNewId $tableName");
      
      $q = "SELECT MAX(id) AS max FROM ". $tableName;
      $this->db->query( $q );
      $row = $this->db->nextRow();
      return ($row['max']+1);
   }

   // DBInspector
   /**
    * Verifica si una tabla existe en la base de datos.
    * @param string tableName nombre de la tabla.
    * @return true si existe la tabla tableName en la base de datos.
    */
   public function tableExists( $tableName ) //: boolean
   {
      Logger::getInstance()->dal_log("DAL::tableExists $tableName en ".$this->database.' para '.$this->appName);
      return $this->db->tableExists($tableName);
   }
   
   public function tableColNames( $tableName ) //: string[]
   {
      $q = "show columns from `$tableName`"; // SOLO FUNCIONA CON ESTAS COMILLAS `, no con '.
      $res = $this->query( $q );
       
      $ret = array();
      foreach ( $res as $colDesc )
      {
         $ret[] = $colDesc['Field'];
      }
      return $ret;
   }
   
   public function tableNames() //: string[] // nombres de todas las tablas de la db seleccionada.
   {
      return $this->db->tableNames();
   }
   
   // FIXME: depende del DBMS...
   public function tableColInfo( $tableName, $col )
   {
      // http://dev.mysql.com/doc/refman/5.0/en/show-columns.html
      $q = "show columns from `$tableName` LIKE `$col`"; // SOLO FUNCIONA CON ESTAS COMILLAS `, no con '.
      $res = $this->query( $q );
      return $res;
   }
}

?>