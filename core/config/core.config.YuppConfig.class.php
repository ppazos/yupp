<?php
/*
 * Created on 18/06/2008
 * core.config.YuppConfig.class.php
 */
 
// TODO: mover a yupp/config (sacar de core)

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
                                         'database' => 'C:\\wamp\\sqlitemanager\\carlitos') // 'C:\\wamp\\sqlitemanager\\test.sqlite')
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
   
   /** 
    * Modo en el que se ejecuta la aplicacion al accederla. 
    * Cuando se instala la aplicacion en produccio debe modificarse el valor a MODE_PROD. */
   private $currentMode = self::MODE_DEV;
   
   /**
    * Indica que accion ejecutar por defecto al ingresar a la aplicacion en el modo actual.
    */
   private $modeDefaultMapping = array(
                                   self::MODE_DEV => // Si se desea acceder al administrador de Yupp no se deben modificar los valores.
                                     array(
                                      'component'  => 'core',
                                      'controller' => 'core',
                                      'action'     => 'index',
                                      'params'     => array()
                                     ),
                                   self::MODE_PROD => // Modificar los valores al poner la aplucacion en produccion.
                                     array(
                                      'component'  => 'portal',
                                      'controller' => 'page',
                                      'action'     => 'display',
                                      'params'     => array('_param_1'=>'index')
                                     ),
                                   self::MODE_TEST => // Todavia no utilizado.
                                     array(
                                      'component'  => 'core',
                                      'controller' => 'core',
                                      'action'     => 'index',
                                      'params'     => array()
                                     )
                                 );
   
   
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
   
   public function getCurrentMode()
   {
   	return $this->currentMode;
   }
   
   /**
    * Retorna el mapping (array de component, controller y accion) para el modo actual.
    */
   public function getModeDefaultMapping()
   {
   	return $this->modeDefaultMapping[ $this->currentMode ];
   }
   
}

?>
