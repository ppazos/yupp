<?php

include_once "core.db.Datatypes.class.php";

// create table
// CREATE TABLE carli ( id BIGINT NOT NULL PRIMARY KEY , nom VARCHAR ( 100 ) , edad INT ) ; 

// insert
// INSERT INTO carli ( id , nom , edad ) VALUES ( NULL , 'pablo' , 26 ) 

/*
// PARA VER SI EXISTE UNA TABLA.
$res = mysql_query("show table status like '$tablename'")
or die(mysql_error());
$table_exists = mysql_num_rows($res) == 1;
*/

// Conector a SQLite
class DatabaseSQLite {

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

// SQLite
   public function connect( $dbhost, $dbuser, $dbpass, $dbName )
   {
      //Logger::getInstance()->log("DatabaseMySQL::connect " . $dbhost ." ". $dbuser ." ". $dbpass ." ". $dbName);

      $this->connection  = new SQLiteDatabase($dbName); // $dbName es el nombre del archivo. No necesito ni host ni user ni pass.                                                        // connection debe ser un handler de archivo...

      if ( $this->connection === false )
      {
         return; // No pudo conectarse
      }
   }

   public function disconnect ()
   {
      //Logger::getInstance()->log("DatabaseMySQL::disconnect");
      // SQLite no tiene disconnect
   }

   // TODO: devolver true o false por si se pudo o no hacer la consulta...
   // SQLite
   public function query( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabaseSQLite::query : " . $query);

      $this->lastQuery = $query;
      $result = NULL;

      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      if (!$result = @$this->connection->query($query)) throw new Exception('La consulta fall&oacute;: ' . sqlite_error_string($this->connection->lastError()) );
      
      $this->queryCount++;
      $this->lastResult = $result;

      return $result;
   }
   

// PARA SQLite necesito otra funcion para update e insert, execute. En MySQL hace update, insert y select con la misma query.
   public function execute( $query )
   {
      Logger::getInstance()->dbmysql_log("DatabaseSQLite::execute : " . $query);
      
   	$this->lastQuery = $query;
      
      // Si hay excepciones, se tiran para la capa de arriba donde se agarran.
      $this->connection->queryExec($query);

      $this->queryCount++;
   }


   // Sirve para iterar por los resultados de la ultima consulta.
   public function nextRow()
   {
      // http://es.codepicks.net/phpmanual/ref.sqlite.html
      // SQLite.next http://es.codepicks.net/phpmanual/function.sqlite-next.html
      if ( $this->lastResult && $this->lastResult->valid() ) // chekear valid si no next tira except...
      {
         $row = $this->lastResult->current(SQLITE_ASSOC);
         $this->lastResult->next();
         
         // Hay un problema con SQLite y es que los nombres de las columnas las devuelve
         // con el alias de la tabla. Si hago select * from a,b, tira a.id, b.pepe, etc.
         // Quiero los atributos SIN alias, para que pueda encontrar los atributos que busco
         // como id, class y deleted. Si no hago esto, deberia cambiar la capa de arriba para
         // que sepa que le pueden venir columnas con prefijos.
         //
         // Saca el alias del nombre de la columna.
         foreach ($row as $key => $value)
         {
            $ipunto = strpos($key, '.');
            if ($ipunto !== false)
            {
               unset($row[$key]);
               $key = substr($key, $ipunto+1);
               $row[$key] = $value;
            }
         }
         return $row;
      }
      return false;
   }

   // Devuelve el numero de resultados (registros) que se obtuvieron con la ultima consulta.
   public function resultCount()
   {
      return $this->lastResult->numRows(); // ??? sqlite_num_rows($resultado)
   }

   public function showLastQuery()
   {
      if ($this->lastResult->numRows() > 0) // (mysql_num_rows($this->lastQuery) > 0)
      {         
         $matrix = $this->lastResult->fetchAll(SQLITE_ASSOC); // retorna filas y columnas...
         foreach ( $matrix as $row )
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
   	return sqlite_error_string($this->connection->lastError());
   }


   // MApeo tipos de SWP con tipos del dbms ===========================================

   // Tipos posibles de atributos
   // Tipos de atributos disponibles (se deberian mapear segun cada DBMS...)
   public function getTextType( $swpType, $maxLength = NULL )
   {
      //Logger::getInstance()->log("DatabaseMySQL::getTextType");

      if ( $maxLength )
      {
         if ( $maxLength > 255 ) return "TEXT";
         else return "VARCHAR(" . $maxLength . ")";

         // http://dev.mysql.com/doc/refman/5.0/en/char.html
         // Values in VARCHAR columns are variable-length strings.
         // The length can be specified as a value from 0 to 255
         // before MySQL 5.0.3, and 0 to 65,535 in 5.0.3 and later versions.
      }

      return "TEXT"; // No tengo restriccion de tamanio.
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
         $maxLength = NULL;
         
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
         
         // FIXME: no tengo este metodo? para que se hace la busqueda aca? En MySQL debe estar igual...
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
      // TODO: SQLite no soporta FKs, se deberia implementar con triggers...
      return;
      
   } // addForeignKeys
   
   /**
    * Verifica si una tabla existe en la base de datos.
    * @param string tableName nombre de la tabla.
    * @return true si existe la tabla tableName en la base de datos.
    */
   public function tableExists( $tableName ) //: boolean
   {
      $res = $this->query( "select name from sqlite_master where name='$tableName'" );
      return $res->numRows() > 0;
   }
   
   public function tableNames() //: string[] // nombres de todas las tablas de la db seleccionada.
   {
      $q = "SELECT tbl_name FROM sqlite_master";
      $res = $this->query( $q );
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
            $where .= $this->evaluateLIKECondition( $condition );
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
      if ( $refVal === 0 ) return "0";
      return (is_string($refVal)) ? "'" . $refVal . "'" : $refVal;
   }
   
   public function evaluateEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ."=". $this->evaluateReferenceValue( $refVal ); // a.b = 666
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ."=". $refAtr->alias.".".$refAtr->attr; // a.b = c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
   }
   
   public function evaluateEEQCondition( Condition $condition )
   {
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      // Idem a EQ en SQLite
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ."=". $this->evaluateReferenceValue( $refVal ); // a.b = 666
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ."=". $refAtr->alias.".".$refAtr->attr; // a.b = c.d
      
      /* STRCMP en SQLite no existe.
      if ( $refVal !== NULL )
         return "STRCMP(". $atr->alias.".".$atr->attr .", BINARY(". $this->evaluateReferenceValue( $refVal ) .")) = 0";
          
      if ( $refAtr !== NULL )
         return "STRCMP(". $atr->alias.".".$atr->attr .", BINARY(". $refAtr->alias.".".$refAtr->attr .")) = 0";
      */ 
      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
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
      $refVal = $condition->getReferenceValue();
      $refAtr = $condition->getReferenceAttribute();
      $atr    = $condition->getAttribute();
      
      if ( $refVal !== NULL )
         return $atr->alias.".".$atr->attr ." ILIKE ". $this->evaluateReferenceValue( $refVal ); // a.b LIKE %666%
      
      if ( $refAtr !== NULL )
         return $atr->alias.".".$atr->attr ." ILIKE ". $refAtr->alias.".".$refAtr->attr; // a.b LIKE c.d

      throw new Exception("Uno de valor o atributo de referencia debe estar presente. " . __FILE__ . " " . __LINE__);
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
