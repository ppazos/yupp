<?php

include_once "core.db.Datatypes.class.php";

// Conector a MySQL
class DatabaseMySQL {

   // OJO cada vez que se incluya pone todo en NULL ! //
   // TODO: Podria ser singleton para simpleficar.

   private $connection = NULL;
   private $lastQuery  = NULL;
   private $lastResult  = NULL;
   private $queryCount; // Cantidad de consultas para un request (deberia ser singleton para poder saber)

   public function __construct()
   {
      //Logger::getInstance()->log("DatabaseMySQL::construct");
      $this->queryCount = 0;
   }

   public function getQueryCount()
   {
      return $this->queryCount;
   }

   public function connect( $dbhost, $dbuser, $dbpass, $dbName )
   {
      //Logger::getInstance()->log("DatabaseMySQL::connect " . $dbhost ." ". $dbuser ." ". $dbpass ." ". $dbName);

      $this->connection = mysql_connect($dbhost, $dbuser, $dbpass);

      //echo "SE CONECTA<br/>";
      //print_r( $this->connection );

      if ( !$this->connection )
      {
         echo "No pudo conectarse : " . mysql_error();
         //exit();
         return;
      }

      //echo "<br />";
      //echo "Connect: ". $this->connection . "<br />";

      $this->selectDB( $dbName );
   }

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

   public function disconnect ()
   {
      //Logger::getInstance()->log("DatabaseMySQL::disconnect");

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

      try
      {
         //Logger::log("\tintenta ejecutar " . $this->connection);
         if (!$result = mysql_query($query, $this->connection)) throw new Exception('La consulta fall&oacute;: ' . mysql_error());
         //ogger::log("\tfin intenta ejecutar");
      }
      catch (Exception $e)
      {
         echo "ERROR: " . $e->getMessage();
      }

      $this->queryCount++;
      $this->lastResult = $result;

      return $result;
   }
   
   // para tener api estandar, es para insert y update. EN mysql es igual a una consulta.
   public function execute( $query )
   {
   	return $this->query( $query );
   }

   // EN LUGAR DE TENER ESTA PORQUE NO HAGO UNA QUE YA TIRE LOS RESULTADOS EN UNA MATRIZ??? xq tengo que armar la matriz afuera igual...
   // MySQL no tiene una funcion para tirar todas las filas de la consulta.
   // Sirve para iterar por los resultados de la ultima consulta..
   public function nextRow()
   {
      if ( $this->lastResult )
      {
         return mysql_fetch_assoc( $this->lastResult );
      }
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


   // MApeo tipos de SWP con tipos del dbms ===========================================

   // Tipos posibles de atributos
   // Tipos de atributos disponibles (se deberian mapear segun cada DBMS...)
   public function getTextType( $swpType, $maxLength = null )
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
            	if ( get_class($constraint) === MaxLengthConstraint )
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

} // DatabaseMySQL

?>
