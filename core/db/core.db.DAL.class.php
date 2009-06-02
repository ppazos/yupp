<?php

// USada por DAL para normalizar nombres.
// FIXME: deberia estar en su propio archivo, talvez en /config, o hacer un dir /conventions y pornerla ahi con YuppConventions.
class DatabaseNormalization {

   public static function table( $tableName )
   {
      //return SWPString::toUnderscoreNotation( $tableName );
      //echo "DBNORM: $tableName <br />";
      //echo "TYPE: ". gettype($tableName) ." <br />";
      return String::firstToLower( $tableName );
      //return $tableName;
   }

   public static function col( $colName )
   {
      //return SWPString::toUnderscoreNotation( $colName );
      return $colName;
   }

   public static function simpleAssoc( $colName )
   {
      //return SWPString::toUnderscoreNotation( $colName ) . "_id";
      return $colName . "_id";
   }

   // PAra saber si el nombre de una columna es una asociacion con otra tabla (FK)
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
   private $url;
   private $user;
   private $pass;
   private $database;

   private static $instance = NULL;

   public static function getInstance()
   {
   	if (!self::$instance) self::$instance = new DAL();
      return self::$instance;
   }

   private function __construct()
   {
      // ===============================================
      $cfg = YuppConfig::getInstance();
      $datasource = $cfg->getDatasource();
      
      // FIXME: Esto es solo para mysql.================
      $this->url      = $datasource['url'];
      $this->user     = $datasource['user'];
      $this->pass     = $datasource['pass'];
      $this->database = $datasource['database'];
      // ===============================================
      
      // FIXME: que base de datos se usa deberia salir de la configuracion. Y la api deberia ser la misma sea cual sea la base.
      //Logger::getInstance()->log("DAL::construct");
      
      // Constructor por configuracion del dbms
      // OBS: cada vez que agregue un soporte nuevo tengo que agregar la opcion al switch.
      
      // TODO: deberia tener una fabrica con esto adentro, y la fabrica tal vez deberia cargar
      // las clases automaticamente en lugar de ir agregando cada tipo de conector en el switch.
      switch( $cfg->getDatabaseType() )
      {
         case YuppConfig::DB_MYSQL:
            YuppLoader::load( "core.db", "DatabaseMySQL" );
            $this->db = new DatabaseMySQL();
         break;
         case YuppConfig::DB_SQLITE:
            YuppLoader::load( "core.db", "DatabaseSQLite" );
            $this->db = new DatabaseSQLite();
         break;
      }
      
      // TODO: que dmbs desde config, perfecto para factory pattern.
      //$this->db = new DatabaseSQLite();
      //$this->db = new DatabaseMySQL();
      $this->db->connect( $this->url, $this->user, $this->pass, $this->database ); // TODO: POR AHORA LOS DATOS PARA ACCEDER A LA BD SE CONFIGURAR AQUI...
   }

   public function __destruct()
   {
      //Logger::getInstance()->log("DAL::destruct");
      $this->db->disconnect();
   }

   // Ejecuta una consulta y devuelve el resultado como una matriz asociativa.
   // FIXME: Devolver referencia para que no copie ?
   public function query( $q )
   {
      Logger::getInstance()->dal_log("DAL::query : " . $q);
      $res = array();
      try
      {
         //Logger::getInstance()->log("\tintento crear");
         if ( !$this->db->query( $q ) ) throw new Exception("ERROR");
         //Logger::getInstance()->log("\tfin intento crear");

         //echo "RES SIZE: " . $this->db->resultCount() . "<br/>";

         while ( $row = $this->db->nextRow() )
         {
            $res[] = $row;
         }
      }
      catch (Exception $e)
      {
         echo $e->getMessage();
         echo $this->db->getLastError(); // DBSTD
      }

      return $res;
   }


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
   public function createTable2($tableName, $pks, $cols, $constraints)
   {
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

      // Obs: REFERENCES no me crea la FK, no se si porque no existe la tabla  la que hago referencia o porque se define de otra forma.
      // Asi funca: ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);


      // VERIFY: posible problema, si estoy creando una tabla con referencias a otra y esa otra no esta creada, capaz salta la base.
      // capaz deveria crear las tablas y luego todas las FKs.

      
   	// TODO:
      $q_ini = "CREATE TABLE " . $tableName . " (";
      $q_end = ");";
      
      
      // Keys obligatorias: name, type.
      // Keys opcionales: default.
      
      //$q_pks = "PRIMARY KEY ( id )";
      $q_pks = "";
      foreach ( $pks as $pk )
      {
         // $q .= DatabaseNormalization::col($attr) ." $dbms_type $nullable , ";
                  
         // =============================================================================================================
         // FIXME: arreglo rapido porque no hay constraints para id, ver el sig. FIXME en PersistentManager en linea 2203
         //    FIXME: c_ins no tiene las restricciones sobre los atributos inyectados.
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
      
      // Esta forma no funciona...
      /*
      $q_fks = "";
      foreach ( $fks as $fk )
      {
         // colName INTEGER REFERENCES other_table(column_name),
//         $q_fks .= $fk['name'] . " " . 
//                   $this->db->getDBType($fk['type'], $constraints ) .
//                   " REFERENCES " . $fk['table'] . "(". $fk['refName'] ."), ";
      }
      */
      
      /* ESTA FORMA FUNCIONA.
      // Si la tabla de referencia no existe, tira un error.
      // Las FKs se deben crear luego de las tablas y agregar mediante: 
      //   ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);
      //
      foreach ( $fks as $fk )
      {
         // FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`)
         $q_fks .= "FOREIGN KEY (" . $fk['name'] . ") " .
                   "REFERENCES " . $fk['table'] . "(". $fk['refName'] ."), ";
      }
      */
      
      $q = $q_ini . $q_pks . substr($q_cols,0,-2) . $q_end; // substr para sacar ", " del final.
      
      //Logger::getInstance()->log( $q );
      
      try
      {
         $this->db->execute( $q );
      }
      catch (Exception $e)
      {
         echo $e->getMessage();
         echo $this->db->getLastError();
      }
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
    * 
    */
   public function addForeignKeys($tableName, $fks)
   {
      // FIXME: las FKs dependen del DBMS, p.e. SQLite no tiene FKs, usa triggers para modelar esta restriccion.
      // Keys obligatorias: name, type, table, refName.
      
   	// ALTER TABLE `prueba` ADD FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`);
      //
      //$q_fks = ""; // Acumula consultas. ACUMULAR CONSULTAS ME TIRA ERROR, VOY A EJECUTARLAS INDEPENDIENTEMENTE, IGUAL PODRIAN ESTAR RODEADAS DE BEGIN Y COMMIT!
      foreach ( $fks as $fk )
      {
         // FOREIGN KEY ( `id` ) REFERENCES `carlitos`.`a` (`id`)
         $q_fks = "ALTER TABLE $tableName " .
                  "ADD FOREIGN KEY (" . $fk['name'] . ") " .
                  "REFERENCES " . $fk['table'] . "(". $fk['refName'] .");";
                  
         //Logger::getInstance()->log( $q_fks );
      
         try
         {
            $this->db->execute( $q_fks );
         }
         catch (Exception $e)
         {
            echo $e->getMessage();
            echo $this->db->getLastError();
         }
      }
   } // addForeignKeys

   /**
    * FIXME: no deberia mandarle el objeto, deberia ser un vector de datos.
    * 
    * @param string          $tableName  nombre de la tabla a generar.
    * //@param PersistentObjet $obj        Intancia del objeto persistente que se quiere salvar.
    * @param $data resultado de hacer PM->getDataFromObject, no importan los valores solo las claves que son los atributos.
    * @param $constraints restricciones sobre los atributos en $data, es necesario para setear nullable o restricciones de largo en strings.
    * @param array           $fks        Vector de resgistros con: atributo de $obj, tabladestino, atributo destino.
    */
   // SQLite> CREATE TABLE ggg (id int, name CHAR(255), email CHAR(255), PRIMARY KEY (id));
   // MySQL> aca le pongo ` y funciona, pero en la doc de la web no le pone, y esas comillas hacen q no me ande el lite.
   public function createTable( $tableName, &$obj )
   //public function createTable( $tableName, $data, $constraints, $fks = null )
   {
      Logger::getInstance()->log("DAL::createTable " . $tableName);

      //$q = "CREATE TABLE `" . $tableName . "` (";
      $q = "CREATE TABLE " . $tableName . " (";

      foreach ( $obj->getAttributeTypes() as $attr => $type )
      {
          // Tengo que ver de que tipo es el campo para crearlo...
          // El nombre del tipo depende del DBMS...
          // Esto es MySQL...
          // Falta ver si un atributo es nullable.
          
          
          // ===========================================================
          // FIXME: esta generando nullables para todos los atributos

          // VERFICA CAMPOS NULLABLES
          /* Ahora son todos nullables menos los inyectados, para hacer mas simple el soporte para herencia.
          $nullable = "";
          if ( $obj->nullable($attr) ) $nullable = "NULL";
          else $nullable = "NOT NULL";
          */
          $nullable = "NULL";
          if ( $obj->isInyectedAttribute( $attr ) ) $nullable = "NOT NULL";

          // Esta solucion me genera NOT NULL para los atributos de superclases mapeados en la misma tabla, y deberia ser NULL.
          //$nullable = "NOT NULL";
          //if ( $obj->nullable($attr) && !$obj->isInyectedAttribute( $attr ) ) $nullable = "NULL";
          //
          // ===========================================================


          //
          // TODO verificar campos string, ver si tienen restriccion de maxlength, 
          // ver que largo puede tener, si es menor que 255 se crea un varchar de eso.
          // Si es mas que eso se crea un TEXT o BLOB.
          //

          //Logger::getInstance()->log( "TIPO: " . $type );

          /*
          CREATE TABLE `tabla_nueva` (
          `id` INT NOT NULL ,
          `user` VARCHAR( 50 ) NOT NULL ,
          PRIMARY KEY ( `id` )
          ) ENGINE = innodb;
          */
          
          /*
           * CREATE TABLE table_name (
           *    id    INTEGER  PRIMARY KEY,
           *    col2  CHARACTER VARYING(20),
           *    col3  INTEGER REFERENCES other_table(column_name),
           * ... )
           *
           */

          $dbms_type = NULL;
          if ( Datatypes::isText( $type ) )
          {
             $maxLength = NULL; // TODO: Falta ver si tengo restricciones de maxlength!!!
             $maxLengthConstraint = $obj->getConstraintOfClass( $attr, 'MaxLengthConstraint' );

             if ($maxLengthConstraint !== NULL) $maxLength = $maxLengthConstraint->getValue();
             
             $dbms_type = $this->db->getTextType( $type, $maxLength ); // Devuelve VARCHAR, TEXT, o el tipo correcto dependiendo del maxlength.
          }
          else if ( Datatypes::isNumber( $type ) )
          {
             $dbms_type = $this->db->getNumericType( $type );
          }
          else if ( Datatypes::isDateTime( $type ) )
          {
             $dbms_type = $this->db->getDateTimeType( $type );
          }
          else
          {
             // Tipo no definido....
             throw new Exception("DAL.createTable: el tipo ($type) del atributo ($attr) no esta definido.");
          }

          //Logger::getInstance()->log( "DBMS TYPE: " . $dbms_type );

          // ===========================================
          // Esta parte genera algo asi:
          // `id` INT NOT NULL ,
          // `nombre` VARCHAR( 50 ) NULL ,
          // ===========================================


          $q .= DatabaseNormalization::col($attr) ." $dbms_type $nullable , ";
          //$q .= "`". DatabaseNormalization::col($attr) ."` $dbms_type $nullable , ";

          /*
          // FIXME: el tipo real depende del dbms, por lo que hay que preguntarle a el cual es el string real.
          switch ( $type )
          {
             ...
             default:
             {
                // Si cae aca es poruqe puede ser una clase persistente, si es tengo que hacer la asoc con la otra tabla.
                // Esto si es una relacion 1..1...

                if ( is_subclass_of($type, 'PersistentObject') )
                {
                   // Ahora agrego dinamicamente el atributo....
                   //$q .= $ins->getAssocAttrName( $type ) . " , "; // FK !!!

                   // FKs MySQL http://dev.mysql.com/doc/refman/5.0/en/innodb-foreign-key-constraints.html
                   // ....
                   // PRIMARY KEY  (`id`),
                   // KEY `FK748CA1805DDE6506` (`tipo_id`),
                   // KEY `FK748CA1805C55741A` (`topografia_id`),
                   // CONSTRAINT `FK748CA1805C55741A` FOREIGN KEY (`topografia_id`) REFERENCES `tipotopografia` (`id`),
                   // CONSTRAINT `FK748CA1805DDE6506` FOREIGN KEY (`tipo_id`) REFERENCES `tipoacceso` (`id`)
                }
                else
                {
                   // Tipo de sociacion no soportado o clase no persistente...
                }
             }
             break;
          }
          */

      }

      $q .= "PRIMARY KEY ( id )"; //$q .= "PRIMARY KEY ( `id` )";
      $q .= ");";

      try
      {
         //Logger::getInstance()->log("\tintento crear");
         //if ( !$this->db->query( $q ) ) throw new Exception( mysql_error() );
         
         // DBSTD
         $this->db->execute( $q );
         
         //Logger::getInstance()->log("\tfin intento crear");
      }
      catch (Exception $e)
      {
         echo $e->getMessage();
         echo $this->db->getLastError(); // DBSTD
      }

      //Logger::log("/DAL::createTable");

   } // createTable



   // Modifica un registro ya existente. DEBE tener el id seteado en los values.
   // FIXME: si la tabla se deriva del objeto no veo la necesidad de pasarle ambos, 
   // anque podria ser necesario para las tablas intermedias de relaciones 1-* y *-*
   // FIXME: no deberia pasarle obj, deberia ser un array de valores.
   //public function update ( $tableName, &$obj )
   public function update ( $tableName, &$data )
   {
      Logger::getInstance()->dal_log("DAL::update " . $tableName . " " . $data['class']);

      // DBG
      //FileSystem::appendLine("LOG.txt", "DAL_UPDATE: " . $tableName . " (" . $obj->getId() . ")");

// TODO:
// MTI
// Si el objeto tiene id, tambien tendra los ids de sus ancestros en $multipleTableIds,
// lo que hay que hacer es igual a insert, solo crear las instancias parciales,
// luego setearle el id a cada una (este paso no estaba en el insert), y luego crear
// la consulta como siempre y hacer el update.

      //Logger::struct( $obj, "PO_INS update" . __FILE__ . " " . __LINE__ ); // EL OBJ LLEGA SIN super_id_C !!!!!!!!

// =================================================================================================================================
// FIXME: esto se deberia hacer en PM y en DAL no se deberian manejar ni POs ni temas de consistencia entre instancias parciales.
// =================================================================================================================================

      $this->update_query2( $data, $tableName );
   
      // UPDATE `carlitos`.`persona_linda` SET `nombre` = 'Pablsdf', `tel` = '709sd9217', `edad` = '3' WHERE `persona_linda`.`id` =1
      // UPDATE `carlitos`.`persona_linda` SET `nombre` = 'Pablon' WHERE `persona_linda`.`id` =1
      
   } // update

   /**
    * @param $tableName nombre de la tabla donde buscar el objeto con identificador $id.
    * @param $id identificador del objeto buscado.
    * @return true si el elemento con identificador $id existe en la tabla $tableName.
    */
   public function exists( $tableName, $id )
   {
      Logger::getInstance()->dal_log("DAL::exists " . $tableName . " (" . $id . ")");

      if ( !$id ) return false;

      $q = "SELECT id FROM $tableName WHERE id=$id";

      $this->db->query( $q );

      if ( $this->db->resultCount() == 1 ) return true;
      return false;
   }


   // Obs: no puedo generar cosas con id = 0 si no da problemas porque se confunde con NULL.
   public function get( $tableName, $id )
   {
      if ( $id === NULL ) throw new Exception("DAL.get: id no puede ser null");

      $q = "SELECT * FROM " . $tableName . " WHERE id=" . $id;

      $this->db->query( $q );

      if ( $row = $this->db->nextRow() )
      {
         return $row;
      }

      // TODO: deberia exceptuar o retornar null?
      throw new Exception("DAL.get: no se encuentra el objeto con id ". $id . " en la tabla " . $tableName);
   }


   /**
    * Lista todos los elementos de una tabla dadas ciertas restricciones como:
    * - max: cantidad maxima de filas devueltas.
    * - offset: desfasaje desde el primer registro de la tabla.
    * - where: condiciones sobre los valores de las columnas de la tabla. Es una instancia de Condition.
    */
   public function listAll( $tableName, $params )
   {
      // TODO: EN PARAMS PDRIA PASAR CONDICIONES SOBRE ATRIBUTOS, PARA HACER BUSQUEDAS QUE ACEPTEN "WHERE", esto lo necesito
      //       para soportar herencia, ya q el listAll debe traer solo instancias de clases de la estructura de herencia
      //       de la clase que necesito (o deberia pasarle tambien la clase que nenecito ??? )
      $limit = "";
      $orderBy = "";

      if ($params && !is_array( $params )) throw new Exceprion("DAL.getAll: params no es un array.");
      else
      {
         // SELECT column FROM table
         // LIMIT 10 OFFSET 10

         // No puede tener offset sin limit! se chekea arriba.
         // Si viene max siempre viene offset, se chekea arriba.
         if (array_key_exists("max", $params))
         {
            $limit = " LIMIT " . $params["max"];
            if (array_key_exists("offset", $params)) $limit .= " OFFSET " . $params["offset"];
         }

         /*
           FROM "nombre_tabla"
           [WHERE "condición"]
           ORDER BY "nombre_columna" [ASC, DESC]
           ...
           ORDER BY "nombre1_columna" [ASC, DESC], "nombre2_columna" [ASC, DESC]
         */

         if (array_key_exists("sort", $params) && $params['sort'])
         {
         	$orderBy = " ORDER BY ". $params["sort"] ." ". $params["dir"] ."";
         }
      }

      // Where siempre viene porque en PM se inyecta las condicioens sobre las subclases (soporte de herencia)
      $q = "SELECT * FROM " . $tableName . " WHERE " . ($params['where']->evaluate()) . $orderBy . $limit;

      //Logger::getInstance()->log( $q );

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
    * 
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
   public function delete2( $class, $id, $logical )
   {
      Logger::add( Logger::LEVEL_DAL, "DAL::delete " . __LINE__ );
      
      if ( $class === NULL ) throw new Exception("DAL.delete: class no puede ser null");
   	if ( $id    === NULL ) throw new Exception("DAL.delete: id no puede ser null");
      
      $tableName = YuppConventions::tableName( $class );
      
      $this->deleteFromTable( $tableName, $id, $logical );
      
   } // delete2


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
   

   // Elimina un objeto unico
   /**
    * FIXME: toda la logica esta deberia estar en PM y solo hacer el delete simple de una row, no deberia haber dependencias a PO.
    * @param $tableName nombre de la tabla correspondiente a la ultima clase de la estructura de herencia de MTI.
    * @param $id identificador del registro a eliminar en la tabla $tableName.
    * @param $logical indica si la eliminacion es logica o fisica.
    */
   /*
   public function delete( $tableName, $id, $logical )
   {
      Logger::getInstance()->dal_log("DAL::delete " . $tableName . " " . $id);

      // DELETE FROM table_name
      // WHERE column_name = some_value
      //
      if ( $id === NULL ) throw new Exception("DAL.delete: id no puede ser null");

      if ($logical)
      {  
         // FIXME: el problema real es que esto se deberia hacer un nivel mas arriba, en PM...
         // FIXME:         
         // FIXME:
         // FIXME: ESTA ROW NO INCLUYE ATRIBUTOS DE OTRAS TABLAS!!! POR ESO LOS ATRIBUTOS DE A y C quedan en NULL...
         $row = $this->get( $tableName, $id ); // obtiene un array de valores!!! no un objeto!!! // FIXME: no es necesario cargar todo para setear un solo atributo... deleted a true.
         
         // ---
         // FIXME: T#24 esto tiene que ver con que get devuelve array asociativo y update un PO, no deberia hacer el mapeo aqui.
         //$realClass = $row['class']; // ES LA ULTIMA CLASE DE LA ESTRUCTURA, QUE TIENE TODOS LOS SUPER_IDs
         //$obj = new $realClass(); // Instancia a devolver, instanciado en la clase correcta.

         // Genero un PO desde cero y le agrego solo los atributos que quiero updatear.
         $obj = new PersistentObject();

         // NECESITO SOLO LOS SUPER_IDS
         $obj->setId( $row['id'] );
         $obj->setClass( $row['class'] );
         $obj->setDeleted( true );
         foreach ($row as $attr => $value)
         {
            if (YuppConventions::isRefName($attr)) // si es super_id_XX
            {
               $obj->addAttribute($attr, Datatypes::INT_NUMBER);
               $obj->aSet( $attr, $value );
            }
         }
         
         $superclasses = ModelUtils::getAllAncestorsOf( $row['class'] ); // puede ser en cualquier orden!
         $superclasses[] = $row['class'];
         $struct = MultipleTableInheritanceSupport::getMultipleTableInheritance( $superclasses ); // Mapa de clases y subclases que se mapean en la misma tabla.
         // [C =>[..], A=>[..], G=>[..]]
         
         //Logger::struct( $struct, "DELETE STRUCT<hr/>" );
         
         // Solo quiero actualizar los campos deleted de cada tabla de MTI.
         $mtiClasses = array_keys( $struct ); // [C, A, G]

         foreach ($mtiClasses as $mtiClass)
         {
            $mtiObj = new PersistentObject( array("deleted"=>true) );
            
            //Logger::getInstance()->log( "ROW CLASS: " . $row['class'] );
            //Logger::getInstance()->log( "MTI CLASS: " . $mtiClass );
            
            // No solo deben ser distintas las clases, tengo que garantizar que se mapean en distintas tablas!
            //if ($row['class'] !== $mtiClass)
            if ( !PersistentManager::isMappedOnSameTable( $row['class'], $mtiClass) ) // FIXME: DAL invocando a PM... esta funcionalidad no deberia estar en PM deberia ser algo del model utils o mti support.
            { 
               $superIdAttr = YuppConventions::superclassRefName( $mtiClass );
               
               //Logger::getInstance()->log( "CLASS: " . $mtiClass . " SUPER ID ATTR: " . $superIdAttr);
               
               $superId = $obj->aGet($superIdAttr);
               
               $mtiObj->setId( $superId );
            }
            else
            {
               $mtiObj->setId( $id );
            }
            
            $mtiObj->setClass( $row['class'] );
            
            //$partialInstances[] = $mtiObj;
            
            //Logger::struct( $mtiObj, "DELETE PartialInstance, mtiObj<hr/>" );
            
            // Actualiza deleted
            $tableName = YuppConventions::tableName( $mtiClass );
            //$this->update_query($mtiObj, $tableName);
            $this->update_query2( $data, $tableName );
         }
      }
      else
      {
         Logger::getInstance()->dal_log("DAL::delete FISICAL " . __LINE__);
         
         // FIXME: no considera instancias parciales y no las elimina!
         
         $q = "DELETE FROM " . $tableName . " WHERE id=" . $id;

         $this->db->execute( $q );
      }
   } // delete
   */
   

   // TODO: un exists que reciba un queryBuilder, seria algo como existsWhere...

   /*
   // Crea un nuevo registro con los valores pasados.
   public function insert( $tableName, &$colNamesAndValues )
   {
      Logger::getInstance()->log("DAL::insert");

      // Tengo que generar un nuevo id.
      $colNamesAndValues["id"] = $this->generateNewId($tableName);

       $q = "INSERT INTO `" . $tableName . "` ( ";

       $attrs = $colNamesAndValues;
       $tableAttrs = "";
       foreach ( $attrs as $attr => $value )
       {
          $tableAttrs .= "`". DatabaseNormalization::col( $attr ) ."` ,";
       }

       $tableAttrs = substr($tableAttrs, 0, sizeof($tableAttrs)-2);

       $q .= $tableAttrs;
       $q .= ") VALUES (";

       // TODO: Si el valor es null tengo que poner null en la tabla, no el string vacio.
       // TODO: Verificar atributos no nullables en null en la instancia de la clase, esto falta agregar cosas a la clase persistente, "las restricciones"
       $tableVals = "";
       foreach ( $attrs as $attr => $value )
       {
          $tableVals .= "'". $value ."' ,"; // FIXME: OJO, si no es literal no deberia poner comillas !!!!
       }

       $tableVals = substr($tableVals, 0, sizeof($tableVals)-2);

       $q .= $tableVals;

       $q .= ");";


       try
       {
         Logger::getInstance()->log("\tintenta insertar");
         if ( !$this->db->query( $q ) ) throw new Exception( mysql_error() );
         Logger::getInstance()->log("\tfin intenta insertar");
       }
       catch (Exception $e)
       {
           echo "MAL MAL MAL!!!!!!!!!!!!!";
           echo $e->getMessage();
       }
   }
*/


   //////////////////////////

   public function insert( $tableName, &$obj )
   {
      // FIXME: obj deberia ser una matriz de valores, no un PO. A DAL no deverian llegar POs.
      //        Y todas las operaciones sobre el PO deberian hacerse tambien en PM.
      Logger::getInstance()->dal_log("DAL::insert " . __FILE__ . " " . __LINE__);

      // DBG
      //FileSystem::appendLine("LOG.txt", "DAL_INSERT: " . $tableName . " (" . $obj->getId() . ")");

      // =======================================================================
      // FIXME: deberia estar en PM
      // Soporte para multiple table inheritance mapping.
      // Si el objeto no tiene mti simplemente se devuelve un array con el mismo objeto de entrada.
      $pinss = MultipleTableInheritanceSupport::getPartialInstancesToSave( $obj );
      
      
      if ( count($pinss) == 1 ) // si no es mti, salva el caso de ObjectReference.
      {
         //Logger::struct( $pinss, "DAL.insert 1 ($tableName)" ); // OBS: si obj es comentario, pinss tiene un objeto que es Entrada, no Comentario.
         //Logger::struct( $obj, "DAL.insert 1 ($tableName)" );
         
         $obj->setId( $this->generateNewId($tableName) );
         
      	// Ahora inserta...
         Logger::getInstance()->dal_log("insert_query call " . __FILE__ . " " . __LINE__ );
         $this->insert_query( $obj, $tableName );
      }
      else
      {
         //Logger::struct( $pinss, "DAL.insert 2 ($tableName)" );
         
         // Procesa el modelo, arma instancias parciales, setea ids...
         foreach ( $pinss as &$partialInstance )
         {
            // Como tableName es el nombre de la tabla del objeto que quiero salvar, 
            // y posiblemente alguna superclase de obj se guarde en otra tabla,
            // para esas instancias parciales tengo que generar el nombre de la tabla.
            // Aunque para la tambla del objeto tengo el nombre, lo genero de nuevo para simplificar logica, de todos modos es el mismo...
            
            $tableName = YuppConventions::tableName( $partialInstance );
            
            // Tengo que generar un nuevo id.
            $partialInstance->setId( $this->generateNewId($tableName) );
      
            // Soporte para MTI
            if ( !PersistentManager::isMappedOnSameTable($obj->getClass(), $partialInstance->getClass()) )
            {
            	$obj->addMultipleTableId($partialInstance->getClass(), $partialInstance->getId());
            }
            else
            {
            	$obj->setId( $partialInstance->getId() ); // Seteo el id del objeto
            }
      
            // ESTO SE DEBERIA HACER EN PM!
            // FIXME: cada partialInstance tiene a su vez que guardar los ids de las superclases en MTIds, para poder guardar el valor en los atributos "super_id_SuperClase".
           
            // FIXME: Problema> NO TIENE LOS ATRIBUTOS super_id... hay que inyectarlos!!!!
            //$obj->updateMultipleTableIds(); // Actualiza para obj los "super_id_SuperClase", TODO: falta hacer lo mismo para las otras instancias parciales.
            $obj->updateSuperIds(); 
      
            // Seteo la clase real en cada una de las instancias parciales, para poder cargar (get, list, find) desde una instancia parcial.
            $partialInstance->setClass( $obj->getClass() );
            
         } // foreach
         
         //Logger::struct( $obj ); // FIXME: aparece seteado el super_id_A pero salva null...
         
         $mtids = $obj->getMultipleTableIds();
         
         //Logger::struct( $mtids, "MTIDs" );
         
         // Setea los atributos super_id_XXX
         foreach ( $pinss as &$partialInstance )
         {
            if (get_parent_class($partialInstance) !== 'PersistentObject') // Setear super_id_SuperClass para cada instancia parcial menos para la clase de nivel 1.
            {
               foreach ( $mtids as $sclass => $id )
               {
                  $superIdAttr = YuppConventions::superclassRefName( $sclass );
                  
                  if ( $partialInstance->hasAttribute( $superIdAttr ) )
                  {
                  	$partialInstance->aSet( $superIdAttr, $id );
                  }
               }
            }
            
            //Logger::struct( $pinss, "DAL.insert 2.1 ($tableName)" );
            
            // Ahora inserta...
            Logger::getInstance()->dal_log("insert_query MTI call " . __FILE__ . " " . __LINE__ );
            $this->insert_query( $partialInstance );
            
         } // foreach
      } // si es mti

      Logger::getInstance()->dal_log("/DAL::insert");
      return $obj->getId(); // DEvuelvo el id generado...
      
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
            if ( is_null($value) ) $tableAttrs .= DatabaseNormalization::col( $attr ) ."=NULL,"; // Si no se pone esto ponia '' y se guardaba 0, mientras necesito que se guarde NULL.
            else if ( is_string($value) ) $tableAttrs .= DatabaseNormalization::col( $attr ) ."='". addslashes($value) ."' ,"; // Debe agregar slashes solo si el valor es string, esto es por si guardo "'" dentro del propio string donde mysql me da error.
            else $tableAttrs .= DatabaseNormalization::col( $attr ) ."='". $value ."',"; // FIXME: Ver si el value es literal...
         }
      }
      $tableAttrs = substr($tableAttrs, 0, sizeof($tableAttrs)-2);
      $q .= $tableAttrs;
      $q .= " WHERE id=" . $data['id'];

      try
      {
         $this->db->execute( $q );
      }
      catch (Exception $e)
      {
          echo $e->getMessage();
          echo $this->db->getLastError();
      }
      
   } // update_query2


   /**
    * FIXME: deberia recibir un array de valores para TODOS los atributos, o sea los declarado en attributeTypes.
    * Se recibe un objecto a la que ya se ha verificado que debe updatearse en la base de datos.
    * @param $object POs a actualizar.
    */
/*SE USA update_query2
   private function update_query( $object, $tableName = NULL )
   {
      if (!$tableName) $tableName = YuppConventions::tableName( $object );

      $q = "UPDATE " . $tableName . " SET "; // DBSTD
      $attrs = $object->getAttributeTypes(); // Atributos simples... (normales e inyectados).  // FIXME: No deberia usar la API de PO en DAL...
      $tableAttrs = "";
      foreach ( $attrs as $attr => $type )
      {
         if ( strcmp($attr, "id") != 0 ) // No updateo el id...
         {
            $value = $object->aGet( $attr );
            if ( is_null($value) ) $tableAttrs .= DatabaseNormalization::col( $attr ) ."=NULL,"; // Si no se pone esto ponia '' y se guardaba 0, mientras necesito que se guarde NULL.
            else $tableAttrs .= DatabaseNormalization::col( $attr ) ."='". $value ."',"; // FIXME: Ver si el value es literal...
         }
      }
      $tableAttrs = substr($tableAttrs, 0, sizeof($tableAttrs)-2);
      $q .= $tableAttrs;
      $q .= " WHERE id=" . $object->getId();

      try
      {
         $this->db->execute( $q );
      }
      catch (Exception $e)
      {
          echo $e->getMessage();
          echo $this->db->getLastError();
      }
      
   } // update_query
*/

   /**
    * Se recibe un objecto a la que ya se ha verificado que debe insertarse en la base de datos.
    * @param $object POs a salvar.
    */
   private function insert_query( $object, $tableName = NULL )
   {
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
         if ( is_null($value) ) $tableVals .= "NULL ,";
         else if ( is_string($value) ) $tableVals .= "'". addslashes($value) ."' ,"; // Debe agregar slashes solo si el valor es string, esto es por si guardo "'" dentro del propio string donde mysql me da error.
         else $tableVals .= "'". $value ."' ,"; // FIXME: OJO, si no es literal no deberia poner comillas !!!!  y si es null deberia guardar null
      }
      $tableVals = substr($tableVals, 0, sizeof($tableVals)-2);
      $q .= $tableVals;
      $q .= ");";

      try
      {
         $this->db->execute( $q );
      }
      catch (Exception $e)
      {
         echo $e->getMessage();
         echo $this->db->getLastError();
      }

   } // insert_query



   public function count( $tableName, $params = array() )
   {
      $q = "SELECT count(id) as cant FROM " . $tableName;
      if (isset($params['where']))
      {
   	   $q .= " WHERE " . ($params['where']->evaluate());
      }

      $this->db->query( $q );

      $row = $this->db->nextRow();

      return $row['cant'];
   }

   public function generateNewId ( $tableName )
   {
      //Logger::getInstance()->log("DAL::generateNewId $tableName");

      $q = "SELECT MAX(id) AS max FROM ". $tableName;

      //$result = $this->db->query( $q );
      
      $this->db->query( $q ); // DBSTD

      $row = $this->db->nextRow(); //mysql_fetch_assoc( $result ); // DBSTD

      //$result = stand_alone_query ( $q );
      //$row = mysql_fetch_assoc( $result ); // FIXME: Hay que ver que pasa cuando es null...
      return ($row['max']+1);
   }

   /*
   // Obtiene el id maximo
   public function maxId( $tableName )
   {
      $q = "SELECT MAX(id) AS max FROM ". $tableName;


      $result = $this->db->query( $q );


      //$result = stand_alone_query ( $q );
      //$row = mysql_fetch_assoc( $result ); // FIXME: Hay que ver que pasa cuando es null...
      return $result['max'];
   }
   */

   public function backup ( $tableName )
   {
      // todo
   }

   public function deleteTable( $tableName )
   {
      // todo
   }

   /* TODO: Respaldo de la base actual
   public function dumpDatabase()
   {
      //include 'config.php';
      //include 'opendb.php';

      //$backupFile = $this->database . date("Y-m-d-H-i-s") . '.sql';
      $backupFile = $this->database . '.sql';

      echo "DUMP: " . $backupFile . "<br/>";
      // ahora no estoy usando pass
      $command = "mysqldump --opt -h $this->url -u $this->user $this->database > $backupFile";
      //$command = "mysqldump --opt -h $this->url -u $this->user -p $this->pass $this->database | gzip > $backupFile";

      echo $command;

      system($command);
      //include 'closedb.php';
   }
   */

   // TODO: eliminacion del esquema actual (todas las tablas)
   
   // DBInspector
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
       
       $q = "show tables like '$tableName'"; // FUNCIONA EN MySQL
       //$q = "show tables like $tableName"; // NO FUNCIONA EN MySQL
       $res = $this->query( $q );
       
       /* Lo que retorna si existe la tabla:
        * Array
        * (
        *     [0] => Array
        *         (
        *             [Tables_in_carlitos (tabla_e)] => tabla_e
        *         )
        * )
        */
       
       return count( $res ) > 0;
   }
   
   public function tableColNames( $tableName ) //: string[]
   {
      /* MySQL:
       * DESCRIBE `person`;
       * or you can use
       * SHOW COLUMNS FROM `person`; 
       * http://dev.mysql.com/doc/refman/5.0/en/show-columns.html
       */
       
       $q = "show columns from `$tableName`"; // SOLO FUNCIONA CON ESTAS COMILLAS `, no con '.
       //$q = "DESCRIBE '$tableName'";
       $res = $this->query( $q );
       
       /* Devuelve un array de descripciones de columnas:
        * Array
        * (
        *    [Field] => id
        *    [Type] => int(11)
        *    [Null] => NO
        *    [Key] => PRI
        *    [Default] => 
        *    [Extra] => 
        * )
        */

      $ret = array();
      foreach ( $res as $colDesc )
      {
         $ret[] = $colDesc['Field'];
      }
       
      return $ret;
   }
   
   public function tableNames() //: string[] // nombres de todas las tablas de la db seleccionada.
   {
      $q = "show tables";
      $res = $this->query( $q );
      return $res;
   }
   
   ///public function tableColType( $tableName, $col ) //: string // tipo de dato de la columna de la tabla.
   public function tableColInfo( $tableName, $col )
   {
       $q = "show columns from `$tableName` LIKE `$col`"; // SOLO FUNCIONA CON ESTAS COMILLAS `, no con '.
       $res = $this->query( $q );
       return $res;
       
       // http://dev.mysql.com/doc/refman/5.0/en/show-columns.html
       
       /* Devuelve un array de descripciones de columnas:
        * Array
        * (
        *    [Field] => id
        *    [Type] => int(11)
        *    [Null] => NO
        *    [Key] => PRI
        *    [Default] => 
        *    [Extra] => 
        * )
        */
   }
   
}

?>