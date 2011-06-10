<?php

include_once "core.db.Datatypes.class.php";

// Conector a MySQL
class DatabaseMySQL {

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
      //Logger::getInstance()->log("DatabaseMySQL::connect " . $dbhost ." ". $dbuser ." ". $dbpass ." ". $dbName);

      // Se le pasa new_link=true para que si se le pasan los mismos parametros, igual cree una nueva conexion, si no devuelve la vieja conexion.
      // Asi, las conexiones de distintas apps son manejadas de forma independiente, aun si usan la misma BD.
      $this->connection = mysql_connect($dbhost, $dbuser, $dbpass, true);

      Logger::getInstance()->log("DatabaseMySQL::connect ". $this->connection);

      if ( !$this->connection )
      {
         throw new Exception( "No pudo conectarse a MySQL: " . mysql_error() );
      }

      $this->selectDB( $dbName );
   }

   private function selectDB ( $dbName )
   {
      //Logger::getInstance()->log("DatabaseMySQL::selectDB");

      //echo "<br />";
      //echo "Select DB: " . $dbName . " " . $this->connection . "<br />";
      if ( ! mysql_select_db ($dbName, $this->connection) ) // Por si estoy trabajando con muchas conecciones
      {
         throw new Exception("Error seleccionando la base de datos <b>$dbName</b>. Verificar que existe.");
      }
   }

   public function disconnect ()
   {
      Logger::getInstance()->log("DatabaseMySQL::disconnect " . $this->connection);

      if ($this->connection !== NULL)
      {
         mysql_close($this->connection); // No necesito pasar la coneccion
         $this->connection = NULL;
      }
   }

   // OJO! lo que devuelve es un recurso mysql... el resultado deberia tratarse internamente...
   // Y devolver true o false por si se pudo o no hacer la consulta...
   public function query( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabaseMySQL::query : " . $query);

      $this->lastQuery = $query;

      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      if (!$result = mysql_query($query, $this->connection))
         throw new Exception('La consulta fall&oacute;: ' . mysql_error());

      $this->queryCount++;
      $this->lastResult = $result;

      return $result;
   }
   
   // para tener api estandar, es para insert y update. EN mysql es igual a una consulta.
   public function execute( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabaseMySQL::execute : " . $query);
      
      $this->lastQuery = $query;
      
      if (!$result = mysql_query($query, $this->connection))
         throw new Exception('La consulta fall&oacute;: ' . mysql_error());
      
      $this->queryCount++;
      
      return true;
   }

   // EN LUGAR DE TENER ESTA PORQUE NO HAGO UNA QUE YA TIRE LOS RESULTADOS EN UNA MATRIZ??? xq tengo que armar la matriz afuera igual...
   // MySQL no tiene una funcion para tirar todas las filas de la consulta.
   // Sirve para iterar por los resultados de la ultima consulta..
   public function nextRow()
   {
      if ( $this->lastResult )
         return mysql_fetch_assoc( $this->lastResult );

      return false;
   }

   // Devuelve el numero de resultados (registros) que se obtuvieron con la ultima consulta.
   public function resultCount()
   {
      return mysql_num_rows($this->lastResult);
   }

   public function showLastQuery()
   {
      if (mysql_num_rows($this->lastQuery) > 0)
      {
         while ($row = mysql_fetch_assoc($this->lastQuery))
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
      return mysql_error();
   }

   // Mapeo tipos de SWP con tipos del dbms ===========================================

   // Tipos posibles de atributos
   // Tipos de atributos disponibles (se deberian mapear segun cada DBMS...)
   public function getTextType( $swpType, $maxLength = NULL )
   {
      //Logger::getInstance()->log("DatabaseMySQL::getTextType");

      if ( $maxLength )
      {
         if ( $maxLength > pow(2,24)) return "LONGTEXT";
         if ( $maxLength > pow(2,16)) return "MEDIUMTEXT";
         if ( $maxLength > 255 )      return "TEXT";
         return "VARCHAR(" . $maxLength . ")";
         
         /* TODO: considerar otros tipos por distintos tamanios
          * BLOB, TEXT  L+2 bytes, donde L  < 2^16
          * MEDIUMBLOB, MEDIUMTEXT  L+3 bytes, donde L < 2^24
          * LONGBLOB, LONGTEXT   L+4 bytes, donde L < 2^32
          */

         // http://dev.mysql.com/doc/refman/5.0/en/char.html
         // Values in VARCHAR columns are variable-length strings.
         // The length can be specified as a value from 0 to 255
         // before MySQL 5.0.3, and 0 to 65,535 in 5.0.3 and later versions.
      }

      return "TEXT"; // No tengo restriccion de tamanio, text por defecto.
   }

   public function getNumericType( $swpType )
   {
      //Logger::getInstance()->log("DatabaseMySQL::getTextType");

      if ($swpType == Datatypes::INT_NUMBER)   return "INT(11)";
      if ($swpType == Datatypes::LONG_NUMBER)  return "BIGINT(20)";
      if ($swpType == Datatypes::FLOAT_NUMBER) return "FLOAT";
      if ($swpType == Datatypes::BOOLEAN)      return "BOOL";

      // No puede llegar aca...
   }

   public function getDateTimeType( $swpType )
   {
      //Logger::getInstance()->log("DatabaseMySQL::getTextType");

      if ($swpType == Datatypes::DATE)     return "DATE";
      if ($swpType == Datatypes::TIME)     return "TIME";
      if ($swpType == Datatypes::DATETIME) return "DATETIME";

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
         throw new Exception("DatabaseMySQL.getDBType: el tipo ($type) no esta definido.");
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
                  "ADD FOREIGN KEY (" . $fk['name'] . ") ".
                  "REFERENCES " . $fk['table'] . "(". $fk['refName'] .");";
                  
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
      /* MySQL:
       * SHOW TABLES [[FROM dbname] LIKE 'tablename']
       *
       * example:
       * show tables from mysql like 'user';
       * show tables like 'user';
       * show tables;
       */
       
      //$q = "show tables like '$tableName'"; // FUNCIONA EN MySQL
      //$q = "show tables like $tableName"; // NO FUNCIONA EN MySQL
  
      $res = $this->query( "show tables like '$tableName'" );
      
      //print_r(  mysql_num_rows($res) );
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
       
      return mysql_num_rows($res) > 0;
   }
   
   public function tableNames() //: string[] // nombres de todas las tablas de la db seleccionada.
   {
      $res = $this->query( "show tables" );
      return $res;
   }
   
   
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
      //Logger::struct($condition, "DatabaseMySQL::evaluateAnyCondition");
      
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
      if ( is_null($refVal) ) return 'NULL'; // Ver si se sigue necesitando por la correccion de IS NULL e IS NOT NULL
      if ( $refVal === 0 ) return "0";
      if ( is_bool($refVal) ) return (($refVal)?'1':'0');
      if ( is_numeric($refVal) ) return $refVal; // Si busca por un numero, aunque el tipo fuera TEXT no encuentra si no se le sacan las comillas.
      
      return (is_string($refVal)) ? "'" . $refVal . "'" : $refVal;
   }
   
   public function evaluateEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ."=". $refAtr->alias.".".$refAtr->attr; // a.b = c.d
      
     // El valor puede ser null porque puedo querer buscar por atributos nulos.
     if ( $refVal !== NULL )
     {
        return $atr->alias.".".$atr->attr ."=". $this->evaluateReferenceValue( $refVal ); // a.b = 666
     }
     else
     {
        // Para comparar por NULL se usa: IS NULL
        // http://www.tutorialspoint.com/mysql/mysql-null-values.htm
        return $atr->alias.".".$atr->attr .' IS NULL';
     }

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateEEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return "STRCMP(". $atr->alias.".".$atr->attr .", BINARY(". $this->evaluateReferenceValue( $refVal ) .")) = 0";
          
      if ( $refAtr !== NULL )
         return "STRCMP(". $atr->alias.".".$atr->attr .", BINARY(". $refAtr->alias.".".$refAtr->attr .")) = 0";
         
      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateNEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ."<>". $refAtr->alias.".".$refAtr->attr; // a.b <> c.d
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ."<>". $this->evaluateReferenceValue( $refVal ); // a.b <> 666
      else
      {
          // Para comparar por NULL se usa: IS NOT NULL
          // http://www.tutorialspoint.com/mysql/mysql-null-values.htm
          return $atr->alias.".".$atr->attr .' IS NOT NULL';
      }
      
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
       // FIXME?: parece que en MySQL por defecto las busquedas no son case sensitive.
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