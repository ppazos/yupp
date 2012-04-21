<?php

// TODO: mover a yupp/config (sacar de core)

/**
 * @author Pablo Pazos Gutierrez <pablo.swp@gmail.com>
 * @name core.config.YuppConfig.class.php
 * 
 * @link http://www.simplewebportal.net/yupp_framework_php_doc/2_3_configuracion_db.html
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
   const DB_POSTGRES = "postgres";
   
   /**
    * Keys de bases de datos disponibles.
    */
   private $available_database_types = array(self::DB_MYSQL, self::DB_SQLITE, self::DB_POSTGRES);
   
   /**
    * Que base de datos se utiliza. Debe estar en la lista $available_database_types.
    */
   //private $database_type = self::DB_MYSQL;
   
   
   // Configuracion de DBs por app
   private $app_datasources = array();
   
   
   /**
    * Configuraciones de las bases de dato disponibles.
    * Solo es necesario configurar la base de datos que se va a utilizar $database_type.
    * Tener varias configuraciones permite cambiar facilmente de base solo cambiando $database_type.
    * Las keys deben estar en $available_database_types.
    */
   /*
   private $dev_datasource = array(
                               self::DB_MYSQL =>
                                  array( 'url' => 'localhost',
                                         'user' => 'root',
                                         'pass' => '',
                                         'database' => 'yupp_dev'),
                               self::DB_SQLITE =>
                                  array( 'url'  => '',
                                         'user' => '',
                                         'pass' => '',
                                         'database' => 'C:\\wamp\\sqlitemanager\\carlitos'), // 'C:\\wamp\\sqlitemanager\\test.sqlite')
                               self::DB_POSTGRES =>
                                  array( 'url' => 'localhost',
                                         'user' => 'postgres',
                                         'pass' => 'root',
                                         'database' => 'yupp_dev')
                             );
   */
   private $default_datasource = array(
                                   /*
                                   self::MODE_DEV  => array(
                                     'type'     => self::DB_POSTGRES,
                                     'url'      => 'localhost',
                                     'user'     => 'postgres',
                                     'pass'     => 'postgres',
                                     'database' => 'yupp_dev'
                                   ),
                                   */
                                   self::MODE_DEV  => array(
                                     'type'     => self::DB_MYSQL,
                                     'url'      => 'localhost',
                                     'user'     => 'root',
                                     'pass'     => '',
                                     'database' => 'yupp_dev'
                                   ),
                                   self::MODE_PROD => array(
                                     'type'     => self::DB_MYSQL,
                                     'url'      => 'localhost',
                                     'user'     => 'root',
                                     'pass'     => '',
                                     'database' => 'yupp_prod'
                                   ),
                                   self::MODE_TEST => array(
                                     'type'     => self::DB_MYSQL,
                                     'url'      => 'localhost',
                                     'user'     => 'root',
                                     'pass'     => '',
                                     'database' => 'yupp_test'
                                   )
                                 );
   
   
   // TODO: agregar que cada app pueda definir db tambien por modo de ejecucion.
   
   
   /**
    * Devuelve la informacion de conexion a la base de datos.
    * @param mode dev, prod, test.
    */
   public function getDatasource( $appName = NULL )
   {
      // Si viene appName y no tengo la configuracion cargada
      if ($appName !== NULL)
      {
         if (isset($this->app_datasources[$appName]) || array_key_exists($appName, $this->app_datasources))
         {
            return $this->app_datasources[$appName];
         }
         
         $appConfigFile = './apps/'.$appName.'/config/db_config.php';
             
         // Trato de cargarla, puede ser que no tenga archivo de configuracion.
         if (file_exists($appConfigFile))
         {
            include_once($appConfigFile); // Tiene definida la variable $db
      
            $this->app_datasources[$appName] = $db[$this->currentMode]; // $db se define en el archivo de configuracion.
           
            // TODO: al igual que el datasource por defecto, el de cada app deberia depender del modo de ejecucion.
            return $this->app_datasources[$appName];
         }
      }

      // Si llega aca es porque, la app no tiene archivo de configuracion, o porque no se paso un nombre de app para cargar su config.

      // Discucion por mode
      return $this->default_datasource[$this->currentMode];
   }
   
   /**
    * Devuelve el DBMS seleccionado.
    */
   public function getDatabaseType()
   {
      return $this->default_datasource[$this->currentMode]['type'];
   }
   
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
                                      'app'        => 'core',
                                      'controller' => 'core',
                                      'action'     => 'index',
                                      'params'     => array()
                                     ),
                                   self::MODE_PROD => // Modificar los valores al poner la aplucacion en produccion.
                                     array(
                                      'app'        => 'portal',
                                      'controller' => 'page',
                                      'action'     => 'display',
                                      'params'     => array('_param_1'=>'index')
                                     ),
                                   self::MODE_TEST => // Todavia no utilizado.
                                     array(
                                      'app'        => 'core',
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
   
   public function getCurrentMode()
   {
      return $this->currentMode;
   }
   
   /**
    * Retorna el mapping (array de app, controller y accion) para el modo actual.
    */
   public function getModeDefaultMapping()
   {
      return $this->modeDefaultMapping[ $this->currentMode ];
   }
}

?>