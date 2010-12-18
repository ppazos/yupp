<?php

//print_r( get_declared_classes() );

include_once ('core/utils/core.utils.ModelUtils.class.php');
include_once ('core/config/core.config.PackageNames.class.php');

class YuppLoader {

   // LOS UNICOS INCLUDES PERMITIDOS SON LOS DEL CLASS LOADER, ALGUNA CLASE ESPECIAL DEL SISTEMA y LOS INCLUDES QUE TIENE EL CLASS LOADER INTERNAMENTE PARA QUE PUEDA FUNCIONAR (LA IDEA ES QUE TENGA LA MENOR CANTIDAD DE DEPENDENCIAS DE OTROS ARCHIVOS Y QUE SEAN SOLO ARCHIVOS DE CONFIGURACION !!!!!)

   /* AL FINAL LO RESOLVI PONIENDO LA INFORMACION DE LAS RUTAS Y PAQUETES EN PackageNames, tengo que ver si es la mejor forma.
    * OJO, ES ONO QUITA QUE UN SCRIPT DESDE AFUERA CONFIGURE EL CLASS LOADER y ESTE USE LA INFO DE SU CONFIGURACION!!!.s
    *
       private $config; // es un map paquete->ubicacion absoluta, sirve para resolver clases de paquetes y saber su ruta desde donde incluirlas.
                        // Ojo, estos son paquetes fisicos definidos por el sistema, no son los paquetes definidos de forma "logica" en los componentes.
                        // Basicamente son paquetes que tienen rutas fijas como: model, views, actions, core, utils, etc.
                        // Visto esto, talvez sea mas util poner los valores aca hardcoded que esperar que me configuren de afuera, pero queda menos flexible a cambios.
                        // Por ahora dejo asi con config desde afuera, luego veo.
   */

   private $loadedClasses;
   private $modelLoaded = false; // Para saber si se cargo el modelo, y no tener que leer de disco en cada request, solo al principio.

   public static function getInstance()
   {
      $instance = NULL;
      if (!YuppSession :: contains("_class_loader_singleton_instance"))
      {
         $instance = new YuppLoader();
         YuppSession :: set("_class_loader_singleton_instance", $instance);
      }
      else
      {
         $instance = YuppSession :: get("_class_loader_singleton_instance");
      }

      return $instance;
   }

   private function __construct()
   {
      $this->loadedClasses = array ();
   }

   /*
      function __sleep()
      {
         echo "sleep<br/>";
   
         $vars = (array)$this;
         foreach ($vars as $key => $val)
         {
             if (is_null($val))
             {
                 unset($vars[$key]);
             }
         }
         return array_keys($vars);
      }
   */

   // /SINGLETONX

   /*
       // Configuracion
       public function configure( $config )
       {
           // TODO: chekeos de tipos
            $this->config = $config;
       }
   
       public function setPackagePath( $package, $path )
       {
            $this->config[$package] = $path;
       }
       // /Configuracion
   */
   // Funcion para ahorrarse tener que llamar al getInstance dedse afuera...
   public static function getLoadedClasses()
   {
      $cl = YuppLoader :: getInstance();
      return $cl->_getLoadedClasses();
   }

   private function _getLoadedClasses()
   {
      return $this->loadedClasses;
   }
   
   
   public static function getLoadedModelClasses()
   {
      $cl = YuppLoader :: getInstance();
      return $cl->_getLoadedModelClasses();
   }
   private function _getLoadedModelClasses()
   {
      $res = array();
      
      foreach( $this->loadedClasses as $fileInfo )
      {
         if ( PackageNames::isModelPackage( $fileInfo['package'] ) )
         {
            $res[] = $fileInfo['class'];
         }
      }
      return $res;
   }
   

   public static function loadModel()
   {
      $cl = YuppLoader :: getInstance();
      $cl->_loadModel();
   }

   /**
    * Carga todo el modelo.
    */
   private function _loadModel()
   {
      $apps = FileSystem::getSubdirNames("./apps");      
      $packs = new PackageNames();

      // FIXME: que pasa si quiero cargar con refresh otras clases? p.e. MySQLDatabase se carga solo una vez porque el que la usa (DAL) es singleton.
      if (!$this->modelLoaded)
      {
         // Carga: component/elComponent/model, para todos los componentes
         foreach ($apps as $app)
         {
            //$path = YuppConventions::getModelPath($component);
            $package = "$app.model";
            $path = YuppConventions::getModelPath($package);
            if (file_exists($path))
            {
               $this->_loadModelRecursive( $path );
            }
         }

         $this->modelLoaded = true;
         // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
         YuppSession :: set("_class_loader_singleton_instance", $this); // actualizo la variable en la session...
         
         //echo "<h2>" . __FILE__ . " (". __LINE__ .") ACTUALIZA CLASS LOADER EN SESSION</h2>";
      }
      else
      {
         //echo "CARGADO";
         //echo "<h2>" . __FILE__ . " (". __LINE__ .") REFRESH</h2>";
         self :: refresh();
      }
      
      //echo "<h1>" . __FILE__ . " (". __LINE__ .") _loadModel TERMINA</h1>";
      
   } // _loadModel
   
   private function _loadModelRecursive( $model_path )
   {
      //echo __FILE__ . ' ' . __LINE__ . " $model_path<br/>";

      $dir = dir($model_path);
      while (false !== ($entry = $dir->read()))
      {
         if ( is_dir($model_path.'/'.$entry) && !String::startsWith($entry, ".") )
         {
            self :: _loadModelRecursive( $model_path . "/" . $entry ); // recursivo
         }
         else if ( !String::startsWith($entry, ".") )
         {
            //echo "<h1>$entry</h1>";
            
            $finfo = FileNames::getFilenameInfo($entry);
            if ($finfo)
            {
               //echo "PACKAGE: " . $finfo['package'] . "</br>";
               //echo "NAME: "    . $finfo['name'] . "</br>";

               // TODO: cargar una clase podria cargar otras, si se declaran loads en esa clase,
               //       por lo que estaria bueno poder verificar aqui si la clase ya esta cargada 
               //       antes de intentar cargarla de nuevo.
               $this->_load($finfo['package'], $finfo['name']);
            }
         }
      }
      $dir->close();
   }


   // Funcion para ahorrarse tener que llamar al getInstance dedse afuera...
   public static function load($package, $clazz)
   {
      $cl = YuppLoader :: getInstance();
      $cl->_load($package, $clazz);
   }

   private function _load($package, $clazz)
   {
      // Tengo que armar el nombre del archvo desde el nombre del paquete y la clase, 
      // ademas tengo que ver la path en la config.

      //echo "PACK $package<br />";
      //echo "<h2>" . __FILE__ . " (". __LINE__ .") LOAD: $package.$clazz </h2>";

      $filename = FileNames::getClassFilename($package, $clazz);

//      echo "FILE $filename<br />";

      // tengo que ver de que tipo es para pedir la ruta correcta...
      // el que sabe la ruta es PackageNames ...
      //
      $path = ".";
      if (PackageNames::isModelPackage($package))
      {
         $path = YuppConventions::getModelPath($package); // "./apps/component/model/package"
      }
      else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
      {
         $path = strtr($package, ".", "/");
      }
      // ... else demas...

      $incPath = $path . "/" . $filename;

      //echo $incPath . "<br />";
//      echo "<h3>" . __FILE__ . " (". __LINE__ .") INC PATH: $incPath</h3>";

      if (!is_file($incPath))
         throw new Exception("YuppLoader::load() - ruta de inclusion errada ($incPath)");

      //    echo "INC: $incPath <br/>";
      include_once $incPath; // esto lo tengo que hacer aunque ya tenga la clase registrada xq si no php no se da cuenta que tiene que incluirla...
      //include ($incPath);
      
      if (!isset ($this->loadedClasses[$incPath])) // registro solo si no se incluyo ya.
      {
         // Guardo la info de la clase cargada.
         $this->loadedClasses[$incPath] = array (
            "package" => $package,
            "class" => $clazz,
            "filename" => $filename
         );
      }
      
      //echo "<h3>" . __FILE__ . " (". __LINE__ .") Termina de incluir</h3>";
      //$vars = (array)$this;
      //print_r($vars);

      // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
      YuppSession :: set("_class_loader_singleton_instance", $this); // actualizo la variable en la session...
      
      //echo "<h3>" . __FILE__ . " (". __LINE__ .") Actualizar CLASS LOADER en Session</h3><br/>";
   }

   public static function loadInterface($package, $interface)
   {
      $cl = YuppLoader :: getInstance();
      $cl->_loadInterface($package, $interface);
   }

   // MISMA LOGICA QUE _load... habra que reusar codigo...
   private function _loadInterface($package, $interface)
   {
      // Tengo que armar el nombre del archvo desde el nombre del paquete y la clase, ademas tengo que ver la path en la config.

      $filename = FileNames::getInterfaceFilename($package, $interface);

      //$path = ".";
      //$packs = new PackageNames();

      // ARMA RUTA FISICA DIRECTAMENTE CON LA RUTA DE PAQUETE (en _load tiene tambien ruta logica a /Model).
      // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
      //
      $path = strtr($package, ".", "/");
      $incPath = $path . "/" . $filename;

      if (!is_file($incPath))
         throw new Exception("YuppLoader::loadInterface() - ruta de inclusion errada ($incPath)");

      include_once ($incPath); // esto lo tengo que hacer aunque ya tenga la clase registrada xq si no php no se da cuenta que tiene que incluirla...

      if (!isset ($this->loadedClasses[$incPath])) // registro solo si no se incluyo ya.
      {
         // Guardo la info de la clase cargada.
         $this->loadedClasses[$incPath] = array (
            "package" => $package,
            "interface" => $interface,
            "filename" => $filename
         );
      }

      // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
      YuppSession :: set("_class_loader_singleton_instance", $this); // actualizo la variable en la session...
   }

   public static function loadScript($package, $script)
   {
      $cl = YuppLoader :: getInstance();
      $cl->_loadScript($package, $script);
   }

   // MISMA LOGICA QUE _load... habra que reusar codigo...
   private function _loadScript($package, $script)
   {
      // Tengo que armar el nombre del archvo desde el nombre del paquete y la clase, ademas tengo que ver la path en la config.
      $filename = FileNames::getScriptFilename($package, $script);

      // ARMA RUTA FISICA DIRECTAMENTE CON LA RUTA DE PAQUETE (en _load tiene tambien ruta logica a /Model).
      // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
      //
      $path = strtr($package, ".", "/");
      $incPath = $path . "/" . $filename;

      if (!is_file($incPath))
         throw new Exception("YuppLoader::loadScript() - ruta de inclusion errada ($incPath)");

      include_once ($incPath); // esto lo tengo que hacer aunque ya tenga la clase registrada xq si no php no se da cuenta que tiene que incluirla...

      /* No quiero guardar los scripts, solo ejecutarlos cuando sean incluidos. Si no cada vez que se haga refresh() los scripts son ejecutados.
      if (!isset ($this->loadedClasses[$incPath])) // registro solo si no se incluyo ya.
      {
         // Guardo la info de la clase cargada.
         $this->loadedClasses[$incPath] = array (
            "package" => $package,
            "script" => $script,
            "filename" => $filename
         );
      }

      // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
      YuppSession :: set("_class_loader_singleton_instance", $this); // actualizo la variable en la session...
      */
   }

   public static function isLoadedClass($package, $clazz)
   {
      $cl = YuppLoader :: getInstance();
      return $cl->_isLoadedClass($package, $clazz);
   }

   private function _isLoadedClass($package, $clazz)
   {
      // IDEM A LOAD...
      
      $filename = FileNames::getClassFilename($package, $clazz);

      $path = ".";
      if (PackageNames::isModelPackage($package))
      {
         //echo "ES MODEL PACKAGE!!!<br/>";
         //$path = $packs->getModelPackagePath();
         //$path = YuppConventions::getModelPath( PackageNames::getModelPackageComponent( $package ) );
         $path = YuppConventions::getModelPath( $package );
      }
      else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
      {
         $path = strtr($package, ".", "/");
      }

      $incPath = $path . "/" . $filename;

      return (array_key_exists($incPath, $this->loadedClasses));
   }

   /**
    * Hace el include en las clases ya cargadas.
    * En ludar de tener que ir al filesystem para cargar las clases del modelo,
    * las carga de la memoria.
    */
   public static function refresh()
   {
      $cl = YuppLoader :: getInstance();

      // FIXME: no recarga las clases del modelo que estan en subdirectorios!

      foreach ($cl->loadedClasses as $classInfo)
      {
         $package = $classInfo['package'];
         $path = ".";
         
         if (PackageNames::isModelPackage($package))
         {
            $path = YuppConventions::getModelPath( $package );
         }
         else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
         {
            $path = strtr($package, ".", "/");
         }
         
         $incPath = $path . "/" . $classInfo['filename'];
         
         if (!is_file($incPath))
         {
            // FIXME: si se tira excepcion, me pasa que al mover archivos me da errores y tengo que borrar la sesion.
            //        igualmente en este caso deberia avisar de alguna forma de que no se encuentra una ruta de inclusion,
            //        por ejemplo haciendo log a disco. Tambien se podria sacar la entrada incorrecta.
            //throw new Exception("YuppLoader::refresh() - ruta de inclusion errada ($incPath)");
         }
         else
         {
            include_once $incPath;
         }
      }
   }
}

?>