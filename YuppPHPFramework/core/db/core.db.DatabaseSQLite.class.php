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

      //$this->connection = mysql_connect($dbhost, $dbuser, $dbpass);
      $this->connection  = new SQLiteDatabase($dbName); // $dbName es el nombre del archivo. No necesito ni host ni user ni pass.
                                                        // connection debe ser un handler de archivo...

      //echo "SQLite SE CONECTA<br/>";
      //print_r( $this->connection );
      //echo gettype($this->connection); // object



      if ( $this->connection === false )
      {
         //echo "No pudo conectarse "; // . mysql_error();
         return;
      }

      //$this->selectDB( $dbName ); // Abrir el archivo ya es seleccionar la base.
   }

/*
   private function selectDB ( $dbName )
   {
      //Logger::getInstance()->log("DatabaseMySQL::selectDB");

      //echo "<br />";
      //echo "Select DB: " . $dbName . " " . $this->connection . "<br />";
      if ( ! mysql_select_db ($dbName, $this->connection) ) // Por si estoy trabajando con muchas conecciones
      {
         echo "Error seleccionando la tabla <b>$dbName</b> de la base de datos.";
         //exit();
         return;
      }
   }
*/

   public function disconnect ()
   {
      //Logger::getInstance()->log("DatabaseMySQL::disconnect");
      /*
      if ($this->connection != NULL)
      {
         mysql_close($this->connection); // No necesito pasar la coneccion
         $this->connection = NULL;
      }
      */
      
      // no me funca el close....
//      if ($this->connection !== NULL) // Para no haer close de una conn q no existe.
//      {
//         $this->connection->close(); // OO de: sqlite_close($manejador_bd);
//         $this->connection = NULL;
//      }
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
      //$this->lastResult = $result; exec no tiene result
   }


   // Sirve para iterar por los resultados de la ultima consulta..
// SQLite
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
   // SQLite
   public function resultCount()
   {
      //return mysql_num_rows($this->lastResult);
      return $this->lastResult->numRows(); // ??? sqlite_num_rows($resultado)
   }

   // SQLite
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
   
}

?>
