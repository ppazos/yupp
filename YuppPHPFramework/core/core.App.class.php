<?php

/**
 * Clase que modela una applicacion web instalada.
 */
class App {

   private $name;
   private $path;
   private $descriptor; // App Descriptor

   /**
    * Crea una instancia de la aplicacion de nombre $name.
    * PRE: $name debe estar contenido en $yupp->getAppNames()
    */
   function __construct( $name )
   {
      $this->name = $name;
      $this->path = './components/'. $name;
   }
   
   public function getName()
   {
      return $this->name;
   }
   
   /**
    * Devuelve la lista de directorios dentro de la aplicacion.
    * [model, controllers, views, services, etc].
    */
   public function getPackages()
   {
      $packages = array();
      
      // Abre directorio de la aplicacion
      $dir = dir($this->path);
      
      // Recorre directorio de la aplicacion
      while (false !== ($package = $dir->read()))
      {
         // Se queda solo con los nombres de los directorios
         if ( !String::startsWith( $package,"." ) && is_dir($package))
         {
            $packages[] = $package;
         }
      }
      
      return $packages;
   }
   
   /**
    * Devuelve un array multidimensional con los nombres de los archivos
    * que forman el modelo de datos de la aplicacion.
    */
   public function getModel()
   {
      $classNames = array();

      $package = $this->path .'/model'; // TODO: si hay subdirectorios, devolverlos tambien.
      //$model_path = YuppConventions::getModelPath( $package );

      if (file_exists($package))
      {
         $classNamesRecur = $this->getModelRecursive( $package );
         $classNames = array_merge($classNames, $classNamesRecur);
      }
      else
      {
          // FIXME: log
          echo 'No existe $package '.__FILE__.' '. __LINE__.'<br/>';
          // devuelve array vacio
      }
      
      return $classNames;
   }
   
   /**
    * Auxiliar recursiva para getModel()
    */
   private function getModelRecursive( $dir )
   {
       $res = array();

       $d = dir($dir);
       while (false !== ($entry = $d->read()))
       {
          if ( !String::startsWith($entry, '.') )
          {
             if ( is_file($dir.'/'.$entry) )
             {
                $res[] = $entry;
             }
             else if ( is_dir($dir.'/'.$entry) )
             {
                $res_recur = $this->getModelRecursive( $dir.'/'.$entry );
                //$res = array_merge($res, $res_recur);
                $res[$entry] = $res_recur;
             }
          }
       }
       $d->close();
       
       return $res;
   }
   
   public function execAction( $controller, $action, $params )
   {
      // El nombre de la clase es el que viene en la url + 'Controller''
      $controllerClassName = strtoupper($controller[0]) . substr($controller, 1) . "Controller";

      YuppLoader::load( 'components.'. $this->name .'.controllers', $controllerClassName );
      
      // TODO:
      // c = new controllerClassName(params)
      // comm = c->action
      // return comm
      
      $cins = new $controllerClassName( $params );
      $comm = $cins->{$action}();
      return $comm;
   }
   
   /**
    * Devuelve true si tiene bootstrap, false en caso contrario.
    */
   public function hasBootstrap()
   {
      $path2BS = 'components/'.$this->name.'/bootstrap';
      $package = strtr($path2BS, '/', '.');
      $bsFile = './'.$path2BS.'/'.$package.'.Bootstrap.script.php';
      
      return file_exists($bsFile);
   }
   
   /**
    * Ejecuta el bootstrap de la app dependiendo del modo de ejecucion.
    * PRE: hasBootstrap()
    * PRE: El usuario la deberia ejecutar solo si esta en modo dev o test.
    *      En modo prod se deberia ejecutar solo cuando la app se instala.
    */
   public function execBootstrap()
   {
      $package = strtr($this->path.'/bootstrap', '/', '.');
      
      // FIXME: el BS a ejecutar debe depender del modo de ejecucion
      YuppLoader::getInstance()->loadScript($package, 'Bootstrap');
   }
   
   public function getDescriptor()
   {
      if ( !isset($this->descriptor) )
      {
         $descriptor = $this->path.'/app.xml';
         if (!file_exists($descriptor))
         {
            throw new Exception('La app '.$this->name.' no tiene descriptor, se esperaba uno en '. $descriptor);
         }
         // I use @ so that it doesn't spit out content of my XML in an error message if the load fails
         $this->descriptor = @simplexml_load_file($descriptor);
      }
      
      return $this->descriptor;
   }
}
?>