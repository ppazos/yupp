<?php
/*
 * Created on 18/06/2008
 * core.config.YuppConfig.class.php
 */
 
class YuppConfig {
	
   private static $instance = NULL;
   public static function getInstance()
   {
   	if (self::$instance === NULL) self::$instance = new YuppConfig();
      return self::$instance;
   }
   
   
   /**
    * Locales disponibles para la aplicacion.
    */
   private $available_locales = array("es", "en", "it");
   
   
   // ==================================================
   // Configuracion de la base de datos
   // ==================================================
   
   /**
    * Tipos de bases de datos que soporta Yupp PHP Framework.
    */
   const DB_MYSQL  = "mysql";
   const DB_SQLITE = "sqlite";
   
   /**
    * Keys de bases de datos disponibles.
    */
   private $available_database_types = array(self::DB_MYSQL, self::DB_SQLITE);
   
   /**
    * Que base de datos se utiliza. Debe estar en la lista $available_database_types.
    */
   private $database_type = self::DB_MYSQL;
   
   /**
    * Configuraciones de las bases de dato disponibles.
    * Solo es necesario configurar la base de datos que se va a utilizar $database_type.
    * Tener varias configuraciones permite cambiar facilmente de base solo cambiando $database_type.
    * Las keys deben estar en $available_database_types.
    */
   private $dev_datasource = array(
                               self::DB_MYSQL =>
                                  array( 'url' => 'localhost',
                                         'user' => 'root',
                                         'pass' => '',
                                         'database' => 'carlitos'),
                               self::DB_SQLITE =>
                                  array( 'url'  => '',
                                         'user' => '',
                                         'pass' => '',
                                         'database' => 'C:\\wamp\\sqlitemanager\\test.sqlite')
                             );
   
   // ==================================================
   // / Configuracion de la base de datos
   // ==================================================
   
   
   /**
    * Modos de ejecucion del framework.
    */
   const MODE_DEV  = "development";
   const MODE_PROD = "production";
   const MODE_TEST = "testing";
   
   public function getAvailableModes()
   {
      return array( self::MODE_DEV, self::MODE_PROD, self::MODE_TEST );
   }
   
   
   /**
    * Devuelve los locales disponibles.
    */
   public function getAvailableLocales()
   {
   	return $this->available_locales;
   }
   
   /**
    * Devuelve la informacion de conexion a la base de datos.
    * @param mode dev, prod, test.
    */
   public function getDatasource( $mode = self::MODE_DEV )
   {
      // TODO: discucion por mode...
   	return $this->dev_datasource[ $this->database_type ];
   }
   
   /**
    * Devuelve el DBMS seleccionado.
    */
   public function getDatabaseType()
   {
   	return $this->database_type;
   }
   
}

?>
