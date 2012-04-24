<?php

include_once ('core/utils/core.utils.ModelUtils.class.php');
include_once ('core/config/core.config.PackageNames.class.php');

class YuppLoader {

   // LOS UNICOS INCLUDES PERMITIDOS SON LOS DEL CLASS LOADER, ALGUNA CLASE ESPECIAL DEL SISTEMA y LOS INCLUDES QUE TIENE EL CLASS LOADER INTERNAMENTE PARA QUE PUEDA FUNCIONAR (LA IDEA ES QUE TENGA LA MENOR CANTIDAD DE DEPENDENCIAS DE OTROS ARCHIVOS Y QUE SEAN SOLO ARCHIVOS DE CONFIGURACION !!!!!)

   /* AL FINAL LO RESOLVI PONIENDO LA INFORMACION DE LAS RUTAS Y PAQUETES EN PackageNames, tengo que ver si es la mejor forma.
    * OJO, ES ONO QUITA QUE UN SCRIPT DESDE AFUERA CONFIGURE EL CLASS LOADER y ESTE USE LA INFO DE SU CONFIGURACION!!!.s
    *
       private $config; // es un map paquete->ubicacion absoluta, sirve para resolver clases de paquetes y saber su ruta desde donde incluirlas.
                        // Ojo, estos son paquetes fisicos definidos por el sistema, no son los paquetes definidos de forma "logica" en las aplicaciones.
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
      return $cl->loadedClasses;
   }
   
   public static function getLoadedModelClasses()
   {
      $cl = YuppLoader :: getInstance();
      
      $res = array();
      foreach( $cl->loadedClasses as $fileInfo )
      {
         if ( PackageNames::isModelPackage( $fileInfo['package'] ) )
         {
            $res[] = $fileInfo['class'];
         }
      }
      return $res;
   }
   
   /**
    * Carga todo el modelo de todas las aplicaciones.
    */
   public static function loadModel()
   {
      $cl = YuppLoader :: getInstance();
      
      // Si estoy en una aplicacion que no es 'core', solo cargo el modelo de esa aplicacion.
      // Si estoy en la aplicacion 'core', carga el modelo de todas las aplicaciones.
      $ctx = YuppContext::getInstance();
      //$apps = array( $ctx->getApp() );
      $apps = array( $ctx->getRealApp() );
      if ($apps[0] == 'core') $apps = FileSystem::getSubdirNames("./apps");
            
      $packs = new PackageNames();

      // FIXME: que pasa si quiero cargar con refresh otras clases? p.e. MySQLDatabase se carga solo una vez porque el que la usa (DAL) es singleton.
      if (!$cl->modelLoaded)
      {
         // Carga: apps/theApp/model, para todos las aplicaciones
         foreach ($apps as $app)
         {
            $package = "$app.model";
            $path = YuppConventions::getModelPath($package);
            if (file_exists($path)) $cl->_loadModelRecursive( $path );
         }

         $cl->modelLoaded = true;
         
         // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
         YuppSession :: set("_class_loader_singleton_instance", $cl); // actualizo la variable en la session...
      }
      else
      {
         self :: refresh();
      }
   }
   
   private function _loadModelRecursive( $model_path )
   {
      $dir = dir($model_path);
      while (false !== ($entry = $dir->read()))
      {
         if ( is_dir($model_path.'/'.$entry) && !String::startsWith($entry, ".") )
         {
            self :: _loadModelRecursive( $model_path . "/" . $entry ); // recursivo
         }
         else if ( !String::startsWith($entry, ".") )
         {
            $finfo = FileNames::getFilenameInfo($entry);
            if ($finfo)
            {
               // Al cargar una clase, esta podria cargar otras, si se declaran loads en esa clase,
               // por lo que verificando si la clase ya esta cargada antes de intentar cargarla de
               // nuevo, ahorraria esos loads en cascada.
               if (!$this->isLoadedClass($finfo['package'], $finfo['name']))
               {
                  $this->load($finfo['package'], $finfo['name']); 
               }
            }
         }
      }
      $dir->close();
   }

   /**
    * @param string $incPath ruta completa de inclusion
    * @param string $package paquete logico del archivo a incluir
    * @param string $type tipo de archivo: class, interface, script
    * @param string $name nombre logica, p.e. para fileName core.basic.String.class.php, $name seria String
    * @param string $fileName nombre del archivo a incluir
    */
   private function loadFile($incPath, $package, $type, $name, $fileName)
   {
      if (!is_file($incPath))
         throw new Exception("YuppLoader::loadFile() - ruta de inclusion errada ($incPath)");

      include_once ($incPath); // esto lo tengo que hacer aunque ya tenga la clase registrada xq si no php no se da cuenta que tiene que incluirla...

      // No se quiere guardar el script, solo ejecutarlo
      if ($type !== 'script' && !isset($this->loadedClasses[$incPath])) // registro solo si no se incluyo ya.
      {
         // Guardo la info de la clase cargada.
         $this->loadedClasses[$incPath] = array (
            "package" => $package,
            $type => $name,
            "filename" => $fileName
         );
      }

      // necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
      YuppSession :: set("_class_loader_singleton_instance", $this); // actualizo la variable en la session...
   }

   // Funcion para ahorrarse tener que llamar al getInstance dedse afuera...
   public static function load($package, $clazz)
   {
      $cl = YuppLoader :: getInstance();
      
      $fileName = FileNames::getClassFilename($package, $clazz);
      $path = ".";
      if (PackageNames::isModelPackage($package))
      {
         $path = YuppConventions::getModelPath($package); // "./apps/theapp/model/package"
      }
      else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
      {
         $path = strtr($package, ".", "/");
      }

      $incPath = $path . "/" . $fileName;
      
      $cl->loadFile($incPath, $package, 'class', $clazz, $fileName);
   }

   public static function loadInterface($package, $interface)
   {
      $cl = YuppLoader :: getInstance();
      
      $fileName = FileNames::getInterfaceFilename($package, $interface);
      $incPath = strtr($package, ".", "/") . "/" . $fileName;
      
      $cl->loadFile($incPath, $package, 'interface', $interface, $fileName);
   }

   public static function loadScript($package, $script)
   {
      $cl = YuppLoader :: getInstance();
      
      $fileName = FileNames::getScriptFilename($package, $script);
      $incPath = strtr($package, ".", "/") . "/" . $fileName;
      
      $cl->loadFile($incPath, $package, 'script', $script, $fileName);
   }

   public static function isLoadedClass($package, $clazz)
   {
      $cl = YuppLoader :: getInstance();
      $filename = FileNames::getClassFilename($package, $clazz);

      $path = ".";
      if (PackageNames::isModelPackage($package))
      {
         $path = YuppConventions::getModelPath( $package );
      }
      else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
      {
         $path = strtr($package, ".", "/");
      }

      $incPath = $path . "/" . $filename;

      return (array_key_exists($incPath, self::getLoadedClasses()));
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
   
   /**
    * Si se llama a esta y luego a loadModel, se deberia cargar todo de nuevo.
    */
   public static function forceReload()
   {
      $cl = YuppLoader :: getInstance();
      $cl->modelLoaded = false;
      YuppSession :: set("_class_loader_singleton_instance", $cl); // actualizo la variable en la session...
   }
}

?>