<?php

include_once "core.db.Datatypes.class.php";

class DatabaseSQLServer {

   // OJO cada vez que se incluya pone todo en NULL ! //
   // TODO: Podria ser singleton para simpleficar.

   private $connection = NULL;
   private $lastQuery = NULL;
   private $lastResult = NULL;
   private $queryCount; // Cantidad de consultas para un request (deberia ser singleton para poder saber)
   private $transactionOn = false;
   private $dbName; // se le llama TABLE CATALOG, hay un segundo nivel como namespace que contiene tablas llamado TABLE SHCEMA = dbo por defecto.
   
   public function __construct()
   {
      $this->queryCount = 0;
   }

   public function getQueryCount()
   {
      return $this->queryCount;
   }
   
   // http://code.google.com/p/yupp/issues/detail?id=123
   public function createDatabase($dbname)
   {
      // FIXME: create if not exists
      $this->execute("CREATE DATABASE $dbname");
   }

   public function connect( $dbhost, $dbuser, $dbpass, $dbName )
   {
      //Logger::getInstance()->log("DatabaseSQLServer::connect " . $dbhost ." ". $dbuser ." ". $dbpass ." ". $dbName);

      //$dbhost = "(local)\sqlexpress";
      // MARS false para que no de errores en las transacciones del save()
      // - http://msdn.microsoft.com/en-us/library/ee376925(v=sql.105).aspx
      // - http://blogs.msdn.com/b/cbiyikoglu/archive/2006/11/21/mars-transactions-and-sql-error-3997-3988-or-3983.aspx
      
      $connectionOptions = array("Database"=>$dbName,
                                 "UID"=>$dbuser,
                                 "PWD"=>$dbpass);

      /* Connect using Windows Authentication. */
      $this->connection = sqlsrv_connect( $dbhost, $connectionOptions);

      Logger::getInstance()->log("DatabaseSQLServer::connect ". $this->connection);

      if ( $this->connection === false )
      {
         throw new Exception( "No pudo conectarse a SQLServer: " . print_r(sqlsrv_errors(), true), 666 ); // 666 es mi codigo de DB no existe...
      }
     
      $this->dbName = $dbName;
   }

   private function selectDB( $dbName )
   {
      //Logger::getInstance()->log("DatabaseSQLServer::selectDB");
      // No se usa, va en el connect
   }

   public function disconnect()
   {
      Logger::getInstance()->log("DatabaseSQLServer::disconnect ". $this->connection);
      
      sqlsrv_close( $this->connection );
      $this->connection = NULL;
   }

   // http://msdn.microsoft.com/es-es/library/ms188929.aspx
   public function withTransaction()
   {
      //$this->execute('BEGIN TRANSACTION');
      sqlsrv_begin_transaction( $this->connection );
      $this->transactionOn = true;
   }
   
   public function commitTransaction()
   {
      if ($this->transactionOn)
      {
         //$this->execute('COMMIT TRANSACTION');
         sqlsrv_commit( $this->connection );
         $this->transactionOn = false;
      }
   }
   
   public function rollbackTransaction()
   {
      if ($this->transactionOn)
      {
         //$this->execute('ROLLBACK TRANSACTION');
         sqlsrv_rollback($this->connection);
         $this->transactionOn = false;
      }
   }

   // OJO! lo que devuelve es un recurso PostgreSQL... el resultado deberia tratarse internamente...
   // Y devolver true o false por si se pudo o no hacer la consulta...
   public function query( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabaseSQLServer::query : " . $query);

      $this->lastQuery = $query;

      // Sin Scrollable STATIC, sql_num_rows tira FALSE
      //   - http://msdn.microsoft.com/en-us/library/hh487160.aspx
      //   - http://php.net/manual/es/function.sqlsrv-num-rows.php
      
      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      //if (!$result = sqlsrv_query($this->connection, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_STATIC)))
      if (!$result = sqlsrv_query($this->connection, $query))
         throw new Exception('La consulta fall&oacute;: ' . print_r(sqlsrv_errors(), true));

      $this->queryCount++;
      $this->lastResult = $result;
      
      return $result;
   }
   
   // para tener api estandar, es para insert y update. EN PostgreSQL es igual a una consulta.
   public function execute( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabaseSQLServer::execute : " . $query);
      
      $this->lastQuery = $query;
      
      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      if (!$result = sqlsrv_query($this->connection, $query))
         throw new Exception('La consulta fall&oacute;: ' . print_r(sqlsrv_errors(), true));

      $this->queryCount++;
      
      return true;
   }

   // EN LUGAR DE TENER ESTA PORQUE NO HAGO UNA QUE YA TIRE LOS RESULTADOS EN UNA MATRIZ??? xq tengo que armar la matriz afuera igual...
   // PostgreSQL no tiene una funcion para tirar todas las filas de la consulta.
   // Sirve para iterar por los resultados de la ultima consulta..
   public function nextRow()
   {
      if ( $this->lastResult )
         return sqlsrv_fetch_array( $this->lastResult, SQLSRV_FETCH_ASSOC );
      
      return false;
   }

   // Devuelve el numero de resultados (registros) que se obtuvieron con la ultima consulta.
   public function resultCount()
   {
      return sqlsrv_num_rows($this->lastResult);
   }

   public function showLastQuery()
   {
      if (sqlsrv_num_rows($this->lastResult) > 0)
      {
         while ($row = sqlsrv_fetch_array($this->lastResult, SQLSRV_FETCH_ASSOC))
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
      return sqlsrv_errors();
   }

   // Mapeo tipos de SWP con tipos del dbms ===========================================

   // Tipos posibles de atributos
   // Tipos de atributos disponibles (se deberian mapear segun cada DBMS...)
   public function getTextType( $swpType, $maxLength = NULL )
   {
      //Logger::getInstance()->log("DatabaseSQLServer::getTextType");

      if ( $maxLength )
      {
         //if ( $maxLength > pow(2,24)) return "LONGTEXT";
         //if ( $maxLength > pow(2,16)) return "MEDIUMTEXT";
         if ( $maxLength > 255 )      return "VARCHAR(MAX)"; // http://stackoverflow.com/questions/564755/sql-server-text-type-vs-varchar-data-type
         return "VARCHAR(" . $maxLength . ")";
         
         /* TODO: considerar otros tipos por distintos tamanios
          * BLOB, TEXT  L+2 bytes, donde L  < 2^16
          * MEDIUMBLOB, MEDIUMTEXT  L+3 bytes, donde L < 2^24
          * LONGBLOB, LONGTEXT   L+4 bytes, donde L < 2^32
          */
      }

      //return "TEXT"; // No tengo restriccion de tamanio, text por defecto.
     return "VARCHAR(MAX)"; // http://stackoverflow.com/questions/564755/sql-server-text-type-vs-varchar-data-type
   }

   //http://msdn.microsoft.com/en-us/library/ms187752.aspx
   public function getNumericType( $swpType )
   {
      //Logger::getInstance()->log("DatabaseSQLServer::getTextType");

      if ($swpType == Datatypes::INT_NUMBER)   return "INT"; // "INT(11)";
      if ($swpType == Datatypes::LONG_NUMBER)  return "BIGINT"; // "BIGINT(20)";
      if ($swpType == Datatypes::FLOAT_NUMBER) return "FLOAT";
      if ($swpType == Datatypes::BOOLEAN)      return "BIT"; //"BOOL";

      // No puede llegar aca...
   }

   //http://msdn.microsoft.com/en-us/library/ms187752.aspx
   public function getDateTimeType( $swpType )
   {
      //Logger::getInstance()->log("DatabaseSQLServer::getTextType");

      if ($swpType == Datatypes::DATE)     return "DATE";
      if ($swpType == Datatypes::TIME)     return "TIME";
      if ($swpType == Datatypes::DATETIME) return "DATETIME";;

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
   public function addForeignKeys($tableName, $fks, $isHasMany = true)
   {
      // TODO: Keys obligatorias: name, type, table, refName.
      
      // ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);
      //
      //$q_fks = ""; // Acumula consultas. ACUMULAR CONSULTAS ME TIRA ERROR, VOY A EJECUTARLAS INDEPENDIENTEMENTE, IGUAL PODRIAN ESTAR RODEADAS DE BEGIN Y COMMIT!
      foreach ( $fks as $fk )
      {
         // owner_id es utilizado como nombre para referencias a tablas intermedias ne hasMany
         // pero si la clase tiene una relacion hasOne llamada 'owner', cuando se crea la FK
         // el nombre que se le asigna es owner_id y puede colisionar con el nombre de hasMany.
         // Lo mismo pasa con ref_id.
         // Aqui se modifica.
         // FIXME: cambiar los nombres de owner_id y ref_id en las tablas intermedias de yupp
         //        para bajar la probabilidad de colision, ej. poniendo jt_ref_id (jt por join table).
         $namePart = $fk['name'];
         if (!$isHasMany && $fk['name'] == 'owner_id') $namePart = 'modowner_id';
         else if (!$isHasMany && $fk['name'] == 'ref_id') $namePart = 'modref_id';
      
         // FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`)
         $q_fks = "ALTER TABLE $tableName ".
                  "ADD CONSTRAINT fk_".$fk['table']."_".$namePart."_".$fk['refName']." ". // En Postgre las FK tienen nombre, usando table, name(nombre del atributo) y refName me aseguro de que es unico.
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
      // http://stackoverflow.com/questions/167576/sql-server-check-if-table-exists
      /* lo que hay en INFORMATION_SCHEA.TABLES
     Array ( 
     [TABLE_CATALOG] => inter_active_tel
     [TABLE_SCHEMA] => dbo 
     [TABLE_NAME] => test_002_cara
     [TABLE_TYPE] => BASE TABLE ) 
      */
      $res = $this->query( "SELECT COUNT(TABLE_NAME) as num FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$tableName'" );

      if ($res === false)
      {
         print_r(sqlsrv_errors(), true);
      }
      
      // Si hay resultado, siempre tiene una row
      $row = sqlsrv_fetch_array( $res, SQLSRV_FETCH_ASSOC );
     
      return $row['num'] > 0;
   }
   
   public function tableNames() //: string[] // nombres de todas las tablas de la db seleccionada.
   {
      return $this->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
   }
   
   /**
    * Devuelve un set de opciones que se usan desde DAL para crear las tablas en la base.
    */
   public function tableOptions()
   {
      return "";
   }
   
   // EVALUACION DE CONSULTAS ======================================================
   //
   public function evaluateQuery( Query $query )
   {
      $select  = $this->evaluateSelect( $query->getSelect() ) .' ';
      $from = '';
      $order   = $this->evaluateOrder( $query->getOrder() ) .' ';
      $groupBy = $this->evaluateGroupBy( $query->getGroupBy() ) .' ';
      
      // SQLServer 2008 no tiene LIMIT, hay que hacerlo con una subquery usando ROWNUM
      // Soluciona problema de paginacion en SQLServer, como esta en DAL::listAll()
      // 
      if ($query->hasLimit())
      {
         // FIXME:
         // El filtro del where que no es el filtro por rowNum
         // debe hacerse en la consulta interna, sino se toman
         // rowNums en la consulta interna que luego no estan
         // en la consulta final, eso afecta al resultado paginado,
         // porque se pagina por rowNum de rows que no matchean el where, ej:
         //  - consulta interna devuelve 1,2,3,4,5,6,7,8
         //  - consulta externa tiene max=3, offset=0
         //  - where matchea solo 2,4,5,7
         //  - el resultado es solo 2, en lugar de 2,4,5 (son los primeros 3 que matchean)
         //
         // problema, el where incluye condiciones que no son sobre la tabla utilizada
         // en la consulta interna, esto afecta?
         
      
         $tableName = $query->getLimitTable();
         $offset = $query->getLimitOffset();
         $max = $query->getLimitMax();
         
         // La consulta interna es para hacer paginacion
         // WHERE: Las condiciones donde dice tableName pone T2 (no puede evaluar condiciones sobre atributos de tablas no mencionados en el FROM)
         //  - http://social.msdn.microsoft.com/Forums/sqlserver/en-US/3b2e0875-e98c-4931-bcb4-e9f449b637d7/the-multipart-identifier-aliasfield-could-not-be-bound         
         
         // Hago el evaluate del from aca porque tengo que cambiar el FROM de tableName por la subconsulta necesaria para el limit
         $queryFrom = $query->getFrom();
         
         $alias;
         
         if (count($queryFrom) == 0)
         {
            // ERROR! es obligatorio por lo menos una!
            throw new Exception("FROM no puede ser vacio");
         }
         else
         {
            /*
            $res = "FROM ";
            foreach ($queryFrom as $table)
            {
               if ( $table->name == $tableName )
               {
                  $alias = $table->alias;
                  
                  $res .= '( SELECT ROW_NUMBER() OVER (ORDER BY id) AS rowNum, * '.
                          'FROM '. $tableName .
                          $this->evaluateWhere( $query->getWhere() ) .
                          ' ) '. $alias .', ';
               }
               else
               {
                  $res .= $table->name .' '. $table->alias .', ';
               }
            }
            $from = substr($res, 0, -2) .' ';
            */
            
            
            $alias = 'subq';
            // * selecciona multiples columnas con mismo nombre en distintas tablas
            $subquery = '( '. $select .', ROW_NUMBER() OVER (ORDER BY '. $tableName .'.id) AS rowNum '.
                        $this->evaluateFrom( $query->getFrom() ) .' '.
                        $this->evaluateWhere( $query->getWhere() ) .' '.
                        $order . $groupBy .
                        ' ) '. $alias .' ';
            $from = 'FROM '. $subquery;
            
            
            $select = 'SELECT * '; // Select para la query ppal. agregaciones y group se harian en la subquery
         }
         
         $where = 'WHERE '. $alias .'.rowNum-1 >= '. $offset .' AND '. $alias .'.rowNum-1 < '.($offset+$max);
         
         return $select . $from . $where;
         
      } // hasLimit
      
      // !hasLimit
      $from  = $this->evaluateFrom( $query->getFrom() ) .' ';
      $where = $this->evaluateWhere( $query->getWhere() ) .' ';
      return $select . $from . $where . $order . $groupBy;
   }
   
   private function evaluateGroupBy( $groupBy )
   {
      if (count($groupBy) != 0)
      {
         $res = "GROUP BY ";
         foreach ($groupBy as $attr)
         {
            $ta = $attr->table .".". $attr->attr;
            
            // Se puede tener GROUP BY funct(table.attr)
            if (!is_null($attr->funct))
            {
               $ta = $attr->funct .'( '. $ta .' )';
            }
            $res .= $ta .", ";
         }
         return substr($res, 0, -2); // Saca ultimo "; "
      }
      
      return "";
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
               $res .= $proj->getTableAlias() .'.'. $proj->getAttrName(); // Projection
            else if ($proj instanceof SelectAggregation)
               $res .= $proj->getName() .'('. $proj->getParam()->getTableAlias() .'.'. $proj->getParam()->getAttrName() .')';
            
            if (($alias = $proj->getAlias()) !== null)
            {
               $res .= ' AS '. $alias;
            }
            
            $res .= ', ';
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
            $res .= $table->name .' '. $table->alias .', ';
         }
         return substr($res, 0, -2); // Saca ultimo "; "
      }
   }

   public function evaluateWhere( Condition $condition, $rewrites = null )
   {
      $where = "";
      if ($condition !== NULL)
      {
         if (is_null($rewrites)) $rewrites = new ArrayObject();
         $where = "WHERE " . $this->evaluateAnyCondition( $condition, $rewrites );
      }
      return $where;
   }
   
   /**
    * Metodo especifico de SQLServer.
    * Necesito pasarle rewrites por la consulta interna necesaria para hacer la paginacion.
   * Ver DAL.listAll
   */
   public function evaluateAnyCondition( Condition $condition, $rewrites = null )
   {
      //Logger::struct($condition, "DatabaseSQLServer::evaluateAnyCondition");
      
      $where = "";
      switch ( $condition->getType() )
      {
         case Condition::TYPE_EQ:
            $where = $this->evaluateEQCondition( $condition, $rewrites );
         break;
         case Condition::TYPE_EEQ:
            $where = $this->evaluateEEQCondition( $condition, $rewrites );
         break;
         case Condition::TYPE_NEQ:
            $where = $this->evaluateNEQCondition( $condition, $rewrites );
         break; 
         case Condition::TYPE_ENEQ:
            $where = $this->evaluateENEQCondition( $condition, $rewrites );
         break;
         case Condition::TYPE_LIKE:
            $where = $this->evaluateLIKECondition( $condition, $rewrites );
         break;
         case Condition::TYPE_ILIKE:
            $where = $this->evaluateILIKECondition( $condition, $rewrites );
         break;
         case Condition::TYPE_GT:
            $where = $this->evaluateGTCondition( $condition, $rewrites );
         break;    
         case Condition::TYPE_LT:
            $where = $this->evaluateLTCondition( $condition, $rewrites );
         break;    
         case Condition::TYPE_GTEQ:
            $where = $this->evaluateGTEQCondition( $condition, $rewrites );
         break;  
         case Condition::TYPE_LTEQ:
            $where = $this->evaluateLTEQCondition( $condition, $rewrites );
         break;  
         case Condition::TYPE_NOT:
            $where = $this->evaluateNOTCondition( $condition, $rewrites );
         break;   
         case Condition::TYPE_AND:
            $where = $this->evaluateANDCondition( $condition, $rewrites );
         break;   
         case Condition::TYPE_OR:
            $where = $this->evaluateORCondition( $condition , $rewrites);
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
      if ( $refVal === NULL ) return 'NULL';
      if ( is_bool($refVal) ) return (($refVal)?'1':'0');
      if ( $refVal === 0 ) return "'0'";
      return (is_string($refVal)) ? "'" . $refVal . "'" : $refVal;
   }
   
   public function evaluateEQCondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      $aalias = $atr->alias;
      if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
      if ( $refAtr !== NULL )
      {
         $ralias = $refAtr->alias;
         if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
       
         return $aalias.".".$atr->attr ."=". $ralias.".".$refAtr->attr; // a.b = c.d
      }
      else
      {
         // El valor puede ser null porque puedo querer buscar por atributos nulos.
         if ( $refVal === NULL )
            return $aalias.".".$atr->attr ." IS NULL";
         
         return $aalias.".".$atr->attr ."=". $this->evaluateReferenceValue( $refVal ); // a.b = 666
      }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateEEQCondition( Condition $condition, $rewrites )
   {
      return $this->evaluateEQCondition( $condition, $rewrites );
      
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
   
   public function evaluateNEQCondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      $aalias = $atr->alias;
      if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
      if ( $refAtr !== NULL )
      {
         $ralias = $refAtr->alias;
         if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
       
         return $aalias.".".$atr->attr ."<>". $ralias.".".$refAtr->attr; // a.b <> c.d
      }
      else
      {
         if ( $refVal === NULL )
            return $aalias.".".$atr->attr ." IS NOT NULL";
         
         return $aalias.".".$atr->attr ."<>". $this->evaluateReferenceValue( $refVal ); // a.b <> 666
      }
      

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateENEQCondition( Condition $condition, $rewrites )
   {
      // TODO
      throw new Exception("evaluateENEQCondition no implementada " . __FILE__ . " " . __LINE__);
   }
   public function evaluateLIKECondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      $aalias = $atr->alias;
      if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
      if ( $refVal !== NULL )
         return $aalias.".".$atr->attr ." LIKE ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
      {
         $ralias = $refAtr->alias;
         if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
         
         return $aalias.".".$atr->attr ." LIKE ". $ralias.".".$refAtr->attr; // a.b LIKE c.d
      }
     
      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateILIKECondition( Condition $condition, $rewrites )
   {
      // LIKE es case sensitive en SQLServer?
      return $this->evaluateLIKECondition( $condition, $rewrites );
   }
   
   public function evaluateGTCondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
     $aalias = $atr->alias;
     if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
      if ( $refVal !== NULL )
         return $aalias.".".$atr->attr ." > ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
     {
        $ralias = $refAtr->alias;
       if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
       
         return $aalias.".".$atr->attr ." > ". $ralias.".".$refAtr->attr; // a.b LIKE c.d
     }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateLTCondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
     $aalias = $atr->alias;
     if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
     
      if ( $refVal !== NULL )
         return $aalias.".".$atr->attr ." < ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
     {
        $ralias = $refAtr->alias;
       if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
       
         return $aalias.".".$atr->attr ." < ". $ralias.".".$refAtr->attr; // a.b LIKE c.d
     }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateGTEQCondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
     $aalias = $atr->alias;
     if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
     
      if ( $refVal !== NULL )
         return $aalias.".".$atr->attr ." >= ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
     {
        $ralias = $refAtr->alias;
       if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
       
         return $aalias.".".$atr->attr ." >= ". $ralias.".".$refAtr->attr; // a.b LIKE c.d
     }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateLTEQCondition( Condition $condition, $rewrites )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
     $aalias = $atr->alias;
     if (isset($rewrites[$aalias])) $aalias = $rewrites[$aalias];
     
     
      if ( $refVal !== NULL )
         return $aalias.".".$atr->attr ." <= ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
     {
        $ralias = $refAtr->alias;
       if (isset($rewrites[$ralias])) $ralias = $rewrites[$ralias];
       
         return $aalias.".".$atr->attr ." <= ". $ralias.".".$refAtr->attr; // a.b LIKE c.d
     }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateNOTCondition( Condition $condition, $rewrites )
   {
      $conds = $condition->getSubconditions();
      if ( count($conds) !== 1 ) throw new Exception("Not debe tener exactamente una condicion para evaluarse. ".__FILE__." ".__LINE__);

      return "NOT (" . $this->evaluateAnyCondition( $conds[0], $rewrites ) . ") ";
   }
   
   public function evaluateANDCondition( Condition $condition, $rewrites )
   {
      $conds = $condition->getSubconditions();
      $res = "(";
      $i = 0;
      $condCount = count( $conds );

      foreach ( $conds as $cond )
      {
         $res .= $this->evaluateAnyCondition( $cond, $rewrites );
         if ($i+1 < $condCount) $res .= " AND ";
         $i++;
      }

      return $res . ")";
   }
   
   public function evaluateORCondition( Condition $condition, $rewrites )
   {
      $conds = $condition->getSubconditions();
      $res = "(";
      $i = 0;
      $condCount = count( $conds );

      foreach ( $conds as $cond )
      {
         $res .= $this->evaluateAnyCondition( $cond, $rewrites );
         if ($i+1 < $condCount) $res .= " OR ";
         $i++;
      }

      return $res . ")";
   }
   //
   // /EVALUACION DE CONSULTAS ======================================================
}

?>