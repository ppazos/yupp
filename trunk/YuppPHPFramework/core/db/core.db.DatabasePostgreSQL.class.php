<?php

include_once "core.db.Datatypes.class.php";

// Conector a PostgreSQL
class DatabasePostgreSQL {

   // OJO cada vez que se incluya pone todo en NULL ! //
   // TODO: Podria ser singleton para simpleficar.

   private $connection = NULL;
   private $lastQuery = NULL;
   private $lastResult = NULL;
   private $queryCount; // Cantidad de consultas para un request (deberia ser singleton para poder saber)

   public function __construct()
   {
      $this->queryCount = 0;
   }

   public function getQueryCount()
   {
      return $this->queryCount;
   }

   public function connect( $dbhost, $dbuser, $dbpass, $dbName )
   {
      //Logger::getInstance()->log("DatabasePostgreSQL::connect " . $dbhost ." ". $dbuser ." ". $dbpass ." ". $dbName);

      // Retorna: PostgreSQL connection resource on success, FALSE on failure. 
      $this->connection = pg_connect("host=$dbhost dbname=$dbName user=$dbuser password=$dbpass");
      
      // "host=sheep port=5432 dbname=mary user=lamb password=foo"
      // connect to a database named "mary" on the host "sheep" with a username and password

      Logger::getInstance()->log("DatabasePostgreSQL::connect ". $this->connection);

      if ( !$this->connection )
      {
         throw new Exception( "No pudo conectarse a PostgreSQL: " . pg_last_error($this->connection) );
      }

      //$this->selectDB( $dbName );
   }

   private function selectDB( $dbName )
   {
      //Logger::getInstance()->log("DatabasePostgreSQL::selectDB");
      // No se usa, va en el connect
   }

   public function disconnect()
   {
      Logger::getInstance()->log("DatabasePostgreSQL::disconnect ". $this->connection);
      
      // Al crear 2 conecciones, se usan 2 instancias de esta clase, pero la
      // conexion en ambas instancias tiene el mismo identificador de recurso.
      // Entonces cuando una se cierra, automaticamente el recuso queda invalido,
      // y cuando la otra instancia cierra, da error en pg_close.
      // Como en la doc (http://www.php.net/manual/en/function.pg-close.php)
      // dice: "Using pg_close() is not usually necessary, as non-persistent open
      // connections are automatically closed at the end of the script", saco el
      // close y dejo que se cierre la conexion de forma automatica.
      // Otra opcion es decirle al pg_connect que cree una conexion nueva cada vez.
      // ver: http://www.php.net/manual/en/function.pg-close.php
       
      $this->connection = NULL;
      
      /*
      if ($this->connection !== NULL)
      {
         if(!pg_close($this->connection))
         {
            //print "Failed to close connection to " . pg_host($this->connection) . ": " .
            pg_last_error($this->connection) . "<br/>\n";
         }

         Logger::getInstance()->log("DatabasePostgreSQL::disconnect Successfully disconnected from database");
         $this->connection = NULL;
      }
      */
   }

   // OJO! lo que devuelve es un recurso PostgreSQL... el resultado deberia tratarse internamente...
   // Y devolver true o false por si se pudo o no hacer la consulta...
   public function query( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabasePostgreSQL::query : " . $query);

      $this->lastQuery = $query;

      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      if (!$result = pg_query($this->connection, $query))
         throw new Exception('La consulta fall&oacute;: ' . pg_last_error($this->connection));

      $this->queryCount++;
      $this->lastResult = $result;

      return $result;
   }
   
   // para tener api estandar, es para insert y update. EN PostgreSQL es igual a una consulta.
   public function execute( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabasePostgreSQL::execute : " . $query);
      
      $this->lastQuery = $query;
      
      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      if (!$result = pg_query($this->connection, $query))
         throw new Exception('La consulta fall&oacute;: ' . pg_last_error($this->connection));

      $this->queryCount++;
      
      return true;
   }

   // EN LUGAR DE TENER ESTA PORQUE NO HAGO UNA QUE YA TIRE LOS RESULTADOS EN UNA MATRIZ??? xq tengo que armar la matriz afuera igual...
   // PostgreSQL no tiene una funcion para tirar todas las filas de la consulta.
   // Sirve para iterar por los resultados de la ultima consulta..
   public function nextRow()
   {
      if ( $this->lastResult )
         return pg_fetch_assoc( $this->lastResult );
      
      return false;
   }

   // Devuelve el numero de resultados (registros) que se obtuvieron con la ultima consulta.
   public function resultCount()
   {
      return pg_num_rows($this->lastResult);
   }

   public function showLastQuery()
   {
      if (pg_num_rows($this->lastQuery) > 0)
      {
         while ($row = pg_fetch_assoc($this->lastQuery))
         {
            echo "<pre>";
            foreach ($row as $key => $value)
            {
               echo "Campo: $key\t- Valor: $value<br>\n";
            }
            echo "</pre>";
         }
      }
   }
   
   public function getLastError()
   {
      return pg_last_error($this->connection);
   }

   // Mapeo tipos de SWP con tipos del dbms ===========================================

   // Tipos posibles de atributos
   // Tipos de atributos disponibles (se deberian mapear segun cada DBMS...)
   public function getTextType( $swpType, $maxLength = NULL )
   {
      //Logger::getInstance()->log("DatabasePostgreSQL::getTextType");

      if ( $maxLength )
      {
         //if ( $maxLength > pow(2,24)) return "LONGTEXT";
         //if ( $maxLength > pow(2,16)) return "MEDIUMTEXT";
         if ( $maxLength > 255 )      return "TEXT";
         return "VARCHAR(" . $maxLength . ")";
         
         /* TODO: considerar otros tipos por distintos tamanios
          * BLOB, TEXT  L+2 bytes, donde L  < 2^16
          * MEDIUMBLOB, MEDIUMTEXT  L+3 bytes, donde L < 2^24
          * LONGBLOB, LONGTEXT   L+4 bytes, donde L < 2^32
          */
      }

      return "TEXT"; // No tengo restriccion de tamanio, text por defecto.
   }

   public function getNumericType( $swpType )
   {
      //Logger::getInstance()->log("DatabasePostgreSQL::getTextType");

      if ($swpType == Datatypes::INT_NUMBER)   return "INTEGER"; // "INT(11)";
      if ($swpType == Datatypes::LONG_NUMBER)  return "BIGINT"; // "BIGINT(20)";
      if ($swpType == Datatypes::FLOAT_NUMBER) return "FLOAT";
      if ($swpType == Datatypes::BOOLEAN)      return "BOOLEAN"; //"BOOL";

      // No puede llegar aca...
   }

   public function getDateTimeType( $swpType )
   {
      //Logger::getInstance()->log("DatabasePostgreSQL::getTextType");

      if ($swpType == Datatypes::DATE)     return "DATE";
      if ($swpType == Datatypes::TIME)     return "TIME";
      if ($swpType == Datatypes::DATETIME) return "TIMESTAMP"; //"DATETIME";

      // No puede llegar aca...
   }
   
   public function getDBType( $type, $constraints )
   {
      $dbms_type = NULL;
   	  if ( Datatypes::isText( $type ) )
      {
         $maxLength = NULL; // TODO: Falta ver si tengo restricciones de maxlength!!!
         
         $maxLengthConstraint = NULL;
         
         if ($constraints !== NULL)
         {
            foreach ( $constraints as $constraint )
            {
               if ( get_class($constraint) === 'MaxLengthConstraint' )
               {
               	$maxLengthConstraint = $constraint;
                  break; // rompe for
               }
            }
         }
         
         //$maxLengthConstraint = $obj->getConstraintOfClass( $attr, MaxLengthConstraint );

         if ($maxLengthConstraint !== NULL) $maxLength = $maxLengthConstraint->getValue();
          
         $dbms_type = $this->getTextType( $type, $maxLength ); // Devuelve VARCHAR, TEXT, o el tipo correcto dependiendo del maxlength.
      }
      else if ( Datatypes::isNumber( $type ) )
      {
         $dbms_type = $this->getNumericType( $type );
      }
      else if ( Datatypes::isDateTime( $type ) )
      {
         $dbms_type = $this->getDateTimeType( $type );
      }
      else
      {
         throw new Exception("DatabasePosgreSQL.getDBType: el tipo ($type) no esta definido.");
      }
      
      return $dbms_type;
      
   } // getDBType
   
   // Operaciones para manipular DBMSs particulares
   
   /**
    * addForeignKeys
    * Se llama luego de crear todas las tablas, sirve para agregar las FKs de una tabla a otras.
    * 
    * @param $tableName nombre de la tabla a agregarle las fks.
    * 
    * @param $fks       claves externas a otras tablas. Array de arrays, cada array interno 
    *                   tiene claves: requeridas(name(string), type(string), table(string), refName(string)),
    *                   "table" es la tabla referenciada por la FK y "refName" es la columna referenciada por la FK.
    * 
    */
   public function addForeignKeys($tableName, $fks)
   {
      // TODO: Keys obligatorias: name, type, table, refName.
      
      // ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);
      //
      //$q_fks = ""; // Acumula consultas. ACUMULAR CONSULTAS ME TIRA ERROR, VOY A EJECUTARLAS INDEPENDIENTEMENTE, IGUAL PODRIAN ESTAR RODEADAS DE BEGIN Y COMMIT!
      foreach ( $fks as $fk )
      {
         // FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`)
         $q_fks = "ALTER TABLE $tableName ".
                  "ADD CONSTRAINT fk_".$fk['table']."_".$fk['name']."_".$fk['refName']." ". // En Postgre las FK tienen nombre, usando table, name(nombre del atributo) y refName me aseguro de que es unico.
                  "FOREIGN KEY (" . $fk['name'] . ") ".
                  "REFERENCES " . $fk['table'] . "(". $fk['refName'] .");";
         
         // ALTER TABLE distributors
         //  ADD CONSTRAINT distfk
         //  FOREIGN KEY (address)
         //  REFERENCES addresses (address) MATCH FULL;
         
         // ALTER TABLE editions
         //  ADD CONSTRAINT foreign_book
         //  FOREIGN KEY (book_id)
         //  REFERENCES books (id);
         
         // ALTER TABLE SALESREPS
         //  ADD CONSTRAINT
         //  FOREIGN KEY (REP_OFFICE)
         //  REFERENCES OFFICES;
         
         // ALTER TABLE alumnos
         //  ADD CONSTRAINT alumnos_fk
         //  FOREIGN KEY (codigo_tutor)
         //  REFERENCES padres_tutores(DNI);
         
         $this->execute( $q_fks );
      }
   } // addForeignKeys
   
   /**
    * Verifica si una tabla existe en la base de datos.
    * @param string tableName nombre de la tabla.
    * @return true si existe la tabla tableName en la base de datos.
    */
   public function tableExists( $tableName ) //: boolean
   {
      // http://archives.devshed.com/forums/databases-124/check-if-postgresql-table-exists-1693460.html
      
      $res = $this->query( "select tablename from pg_tables where tablename='$tableName'" );
      
      /*
      while ($row = $this->nextRow())
      {
         print_r( $row );
      }
      */
      
      //print_r( pg_num_rows($res) );
      //print_r( $res );
      
      /* Lo que retorna si existe la tabla:
       * Array
       * (
       *     [0] => Array
       *         (
       *             [Tables_in_carlitos (tabla_e)] => tabla_e
       *         )
       * )
       */
       
      return pg_num_rows($res) > 0;
   }
   
   public function tableNames() //: string[] // nombres de todas las tablas de la db seleccionada.
   {
      $res = $this->query( "select tablename from pg_tables" );
      return $res;
   }

/*
   public function createTable($tableName, $pks, $cols, $constraints)
   {
      Logger::getInstance()->log("DatabasePostgreSQL::createTable: " . $tableName);
      // TODO:
      // ESTA LLAMADA: $dbms_type = $this->db->getTextType( $type, $maxLength );
      // Deberia cambiarse por: $this->db->getDBType( $attrType, $attrConstraints ); // Y todo el tema de ver el largo si es un string lo hace adentro.
      
      //         CREATE TABLE `tabla_nueva` (
      //          `id` INT NOT NULL ,
      //          `user` VARCHAR( 50 ) NOT NULL ,
      //          PRIMARY KEY ( `id` )
      //         ) ENGINE = innodb;  
      //          
      //         CREATE TABLE table_name (
      //           id    INTEGER  PRIMARY KEY, << utiliza esta forma de declarar PKs, obs, no puedo declarar mas de una, de la otra forma si!
      //           col2  CHARACTER VARYING(20),
      //           col3  INTEGER REFERENCES other_table(column_name), << usa esta forma de declaracon de FKs
      //         ... )
      
      // CREATE TABLE films (
      //  code        char(5) CONSTRAINT firstkey PRIMARY KEY,
      //  title       varchar(40) NOT NULL,
      //  did         integer NOT NULL,
      //  date_prod   date,
      //  kind        varchar(10),
      //  len         interval hour to minute
      // );
      
      // Keys obligatorias: name, type.
      // Keys opcionales: default.
      $q_ini = "CREATE TABLE " . $tableName . " (";
      $q_end = ");";
      
      $q_pks = "";
      foreach ( $pks as $pk )
      {
         $constraintsOrNull = (isset($constraints[$pk['name']])) ? $constraints[$pk['name']] : NULL;
         $q_pks .= $pk['name'] . " " . 
                   $this->getDBType($pk['type'], $constraintsOrNull ) . " " .
                   ((array_key_exists('default', $pk)) ? "DEFAULT " . $pk['default'] : '') . // si hay default lo pone 
                   " PRIMARY KEY, "; // TODO!
      }
      
      // Keys obligatorias: name, type.
      // Keys opcionales: default, nullable.
      
      $q_cols = "";
      foreach ( $cols as $col )
      {
         // $q .= DatabaseNormalization::col($attr) ." $dbms_type $nullable , ";
  
         // =============================================================================================================
         // FIXME: arreglo rapido porque no hay constraints para id, ver el sig. FIXME en PersistentManager en linea 2203
         //    FIXME: c_ins no tiene las restricciones sobre los atributos inyectados.
         $constraintsOrNull = (isset($constraints[$col['name']])) ? $constraints[$col['name']] : NULL;
         $q_cols .= $col['name'] . " " . 
                    $this->db->getDBType($col['type'], $constraintsOrNull ) . " " .
                    ((array_key_exists('default', $col)) ? "DEFAULT " . $col['default'] : '') . // si hay default lo pone 
                    ((array_key_exists('nullable', $col) && $col['nullable']) ? " NULL" : " NOT NULL") . // Si la clave nullable esta y si el ooleano en nullable es true, pone NULL.
                    ", ";
      }

      // Keys obligatorias: name, type, table, refName.
      
      $q = $q_ini . $q_pks . substr($q_cols,0,-2) . $q_end; // substr para sacar ", " del final.

      //Si hay una excepcion, va a la capa superior.
      $this->execute( $q );
      
   } // createTable
*/

/* Pense que capaz habia que implementar algo especial pero con lo que hay en DAL funciona asi que no es necesario.
   public function count( $tableName, $params = array() )
   {
      //Logger::getInstance()->log("DAL::count $tableName");
      
      $q = "SELECT count(id) FROM " . $tableName;
      if (isset($params['where']))
      {
         $q .= " WHERE " . ( $this->evaluateAnyCondition( $params['where'] ) );
      }

      $this->query( $q );
      $row = $this->nextRow();

//print_r($row);

      return $row[0];
   }
   
   public function generateNewId ( $tableName )
   {
      //Logger::getInstance()->log("DAL::generateNewId $tableName");

      $this->query( "SELECT MAX(id) FROM ". $tableName );
      $row = $this->nextRow();

//print_r($row);

      if ( empty($row[0]) ) return 1;
      return ($row[0]+1);
   }
*/
   
   // EVALUACION DE CONSULTAS ======================================================
   //
   public function evaluateQuery( Query $query )
   {
      $select = $this->evaluateSelect( $query->getSelect() ) . " ";
      $from   = $this->evaluateFrom( $query->getFrom() )   . " ";
      $where  = $this->evaluateWhere( $query->getWhere() )  . " ";
      $order  = $this->evaluateOrder( $query->getOrder() )  . " ";
      $limit  = ""; // TODO: no tengo limit??

      return $select . $from . $where . $order . $limit;
   }
   
   private function evaluateSelect( Select $select )
   {
      // FIXME: no todos los objetos tienen porque ser proyecciones,
      //        pueden haber agregaciones y funciones.
      $projections = $select->getAll();
      if (count($projections) == 0) return "SELECT *";
      else
      {
         $res = "SELECT ";
         foreach ($projections as $proj)
         {
            // FIXME: la aggregation puede ser una evaluacion
            //        recursiva porque param es SelectItem y
            //        puede ser que tenga una agg adentro, asi sucesivamente.
            if ($proj instanceof SelectAttribute)
               $res .= $proj->getAlias() . "." . $proj->getAttrName() . ", "; // Projection
            else if ($proj instanceof SelectAggregation)
               $res .= $proj->getName() . "(". $proj->getParam()->getAlias() . "." . $proj->getParam()->getAttrName() ."), ";
         }
         return substr($res, 0, -2); // Saca ultimo "; "
      }
   }

   private function evaluateFrom( $from )
   {
      if (count($from) == 0)
      {
         // ERROR! es olbigatorio por lo menos una!
         throw new Exception("FROM no puede ser vacio");
      }
      else
      {
         $res = "FROM ";
         foreach ($from as $table)
         {
            $res .= $table->name . " " . $table->alias . ", ";
         }
         return substr($res, 0, -2); // Saca ultimo "; "
      }
   }

   public function evaluateWhere( Condition $condition )
   {
      $where = "";
      if ($where !== NULL)
      {
         $where = "WHERE " . $this->evaluateAnyCondition( $condition );
      }
      return $where;
   }
   
   public function evaluateAnyCondition( Condition $condition )
   {
      //Logger::struct($condition, "DatabasePostgreSQL::evaluateAnyCondition");
      
      $where = "";
      switch ( $condition->getType() )
      {
         case Condition::TYPE_EQ:
            $where = $this->evaluateEQCondition( $condition );
         break;
         case Condition::TYPE_EEQ:
            $where = $this->evaluateEEQCondition( $condition );
         break;
         case Condition::TYPE_NEQ:
            $where = $this->evaluateNEQCondition( $condition );
         break; 
         case Condition::TYPE_ENEQ:
            $where = $this->evaluateENEQCondition( $condition );
         break;
         case Condition::TYPE_LIKE:
            $where = $this->evaluateLIKECondition( $condition );
         break;
         case Condition::TYPE_ILIKE:
            $where = $this->evaluateILIKECondition( $condition );
         break;
         case Condition::TYPE_GT:
            $where = $this->evaluateGTCondition( $condition );
         break;    
         case Condition::TYPE_LT:
            $where = $this->evaluateLTCondition( $condition );
         break;    
         case Condition::TYPE_GTEQ:
            $where = $this->evaluateGTEQCondition( $condition );
         break;  
         case Condition::TYPE_LTEQ:
            $where = $this->evaluateLTEQCondition( $condition );
         break;  
         case Condition::TYPE_NOT:
            $where = $this->evaluateNOTCondition( $condition );
         break;   
         case Condition::TYPE_AND:
            $where = $this->evaluateANDCondition( $condition );
         break;   
         case Condition::TYPE_OR:
            $where = $this->evaluateORCondition( $condition );
         break;
      }
      
      return $where;
   }
   
   private function evaluateOrder( $order )
   {
      if (count($order) > 0)
      {
         $res = "ORDER BY ";
         foreach ($order as $order)
         {
            $res .= $order->alias . "." . $order->attr . " " . $order->dir . ", ";
         }
         return substr($res, 0, -2); // Saca ultimo "; "
      }
   }
   
//   private function evaluateReferenceAttribute(  )
//   {
//      return $this->referenceAttribute->alias . "." . $this->referenceAttribute->attr ;
//   }
   
   private function evaluateReferenceValue( $refVal )
   {
      // Si es 0 me devuelve null...
      if ( is_null($refVal) ) return 'NULL';
      if ( is_bool($refVal) ) return (($refVal)?'TRUE':'FALSE');
      if ( $refVal === 0 ) return "'0'";
      return (is_string($refVal)) ? "'" . $refVal . "'" : $refVal;
   }
   
   public function evaluateEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ."=". $refAtr->alias.".".$refAtr->attr; // a.b = c.d
      else
      {
         // El valor puede ser null porque puedo querer buscar por atributos nulos.
         //if ( $refVal !== NULL )
            return $atr->alias.".".$atr->attr ."=". $this->evaluateReferenceValue( $refVal ); // a.b = 666
      }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateEEQCondition( Condition $condition )
   {
      return $this->evaluateEQCondition( $condition );
      
      /*
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return "STRCMP(". $atr->alias.".".$atr->attr .", ". $this->evaluateReferenceValue( $refVal ) .") = 0";
          
      if ( $refAtr !== NULL )
         return "STRCMP(". $atr->alias.".".$atr->attr .", ". $refAtr->alias.".".$refAtr->attr .") = 0";
         
      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
      */
   }
   
   public function evaluateNEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ."<>". $this->evaluateReferenceValue( $refVal ); // a.b <> 666
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ."<>". $refAtr->alias.".".$refAtr->attr; // a.b <> c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateENEQCondition( Condition $condition )
   {
      // TODO
      throw new Exception("evaluateENEQCondition no implementada " . __FILE__ . " " . __LINE__);
   }
   public function evaluateLIKECondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ." LIKE ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ." LIKE ". $refAtr->alias.".".$refAtr->attr; // a.b LIKE c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateILIKECondition( Condition $condition )
   {
       // FIXME?: parece que en PostgreSQL por defecto las busquedas no son case sensitive.
       return $this->evaluateLIKECondition( $condition );
   }
   
   public function evaluateGTCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ." > ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ." > ". $refAtr->alias.".".$refAtr->attr; // a.b LIKE c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateLTCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ." < ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ." < ". $refAtr->alias.".".$refAtr->attr; // a.b LIKE c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateGTEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ." >= ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ." >= ". $refAtr->alias.".".$refAtr->attr; // a.b LIKE c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateLTEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ." <= ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ." <= ". $refAtr->alias.".".$refAtr->attr; // a.b LIKE c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateNOTCondition( Condition $condition )
   {
      $conds = $condition->getSubconditions();
      if ( count($conds) !== 1 ) throw new Exception("Not debe tener exactamente una condicion para evaluarse. ".__FILE__." ".__LINE__);

      return "NOT (" . $this->evaluateAnyCondition( $conds[0] ) . ") ";
   }
   
   public function evaluateANDCondition( Condition $condition )
   {
      $conds = $condition->getSubconditions();
      $res = "(";
      $i = 0;
      $condCount = count( $conds );

      foreach ( $conds as $cond )
      {
         $res .= $this->evaluateAnyCondition( $cond );
         if ($i+1 < $condCount) $res .= " AND ";
         $i++;
      }

      return $res . ")";
   }
   
   public function evaluateORCondition( Condition $condition )
   {
      $conds = $condition->getSubconditions();
      $res = "(";
      $i = 0;
      $condCount = count( $conds );

      foreach ( $conds as $cond )
      {
         $res .= $this->evaluateAnyCondition( $cond );
         if ($i+1 < $condCount) $res .= " OR ";
         $i++;
      }

      return $res . ")";
   }
   //
   // /EVALUACION DE CONSULTAS ======================================================
}

?>