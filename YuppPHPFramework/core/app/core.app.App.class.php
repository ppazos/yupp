<?php

YuppLoader::load('core.support', 'YuppContext');

/**
 * Clase que modela una applicacion web instalada.
 */
class App {

   private $name;
   private $path;
   private $descriptor; // App Descriptor
   private $controller; // Se setea en el ultimo controller para el que se llamo a execAction, es usado en getExecActionParams
   
   static function getCurrent()
   {
      $ctx = YuppContext::getInstance();
      return new App($ctx->getApp());
   }

   /**
    * Crea una instancia de la aplicacion de nombre $name.
    * PRE: $name debe estar contenido en $yupp->getAppNames()
    */
   function __construct( $name )
   {
      $this->name = $name;
      $this->path = './apps/'. $name;
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
         if ( !String::startsWith( $package, "." ) && is_dir($this->path.'/'.$package))
         {
            $packages[] = $package;
         }
      }
      
      return $packages;
   }
   
   /**
    * Devuelve un array multidimensional con los nombres de los archivos
    * que forman el modelo de datos de la aplicacion. Considera la estructura
    * de directorios de /model.
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
          // Simplemente la app no tiene paquete /model
          //echo 'No existe '. $package .' '.__FILE__.' '. __LINE__.'<br/>';
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
   
   /**
    * $controllerName viene en minusculas, p.e. si se refiere a "EntradaBlogController" debera venir "entradaBlog".
    * $pathToControllers se usa para la recursion.
    */
   public function hasController( $controllerName, $pathToControllers = NULL )
   {
      if ($pathToControllers == NULL)
      {
         $pathToControllers = $this->path .'/controllers/';
      }
      
      $dir = dir($pathToControllers);
      
      //echo $pathToControllers.'<br/>';
      
      // Sufijo del nombre del archivo, no incluye parte del paquete, p.e. si el nombre completo es
      // blog.EntradaBlogController.class.php, $controllerFileNameSufix == 'EntradaBlogController.class.php'
      $controllerFileNameSufix = String::firstToUpper($controllerName) .'Controller.class.php';
      
      // Si encuentro subdirectorios que pueden tener controllers, los voy guardando para hacer recursion.
      $recursiveSubdirs = array();
      
      // Recorre directorio de la aplicacion
      while (false !== ($fileOrDir = $dir->read()))
      {
         //echo $test.'<br/>';
         // Se queda solo con los nombres de los directorios
         if (is_file($pathToControllers.$fileOrDir) && String::endsWith($fileOrDir, $controllerFileNameSufix))
         {
            return true;
         }
         else if (is_dir($pathToControllers.$fileOrDir) && $fileOrDir != '.' && $fileOrDir != '..')
         {
            $recursiveSubdirs[] = $fileOrDir;
         }
      }
      
      // Recursion
      foreach ($recursiveSubdirs as $subdir)
      {
         //echo "$subdir<br/>";
         if ($this->hasController($controllerName, $subdir)) return true;
      }
      
      return false;
   }
   
   public function execAction( $controller, $action, $params )
   {
      // El nombre de la clase es el que viene en la url + 'Controller''
      $controllerClassName = strtoupper($controller[0]) . substr($controller, 1) . "Controller";

      YuppLoader::load( 'apps.'. $this->name .'.controllers', $controllerClassName );
      
      // The controller action should have 'Action' suffix4
      $actionMethod = $action;
      if (!String::endsWith($actionMethod, 'Action')) $actionMethod .= 'Action';
      
      
      $this->controller = new $controllerClassName( $params );
      
      // TODO: Can throw method doesnt exist exception, verify if class method exists
      if (!method_exists($this->controller, $actionMethod))
      {
         // Si la accion no existe, se puede estar llamando al render de una vista
         if (file_exists('apps/'.$this->appName.'/views/'.$this->controllerName.'/'.$this->actionName.'.view.php'))
         {
            // Render directo de la vista
            return $this->render($actionMethod);
         }
         else // No existe la accion ni la vista, devuelve 404 not found
         {
            return ViewCommand::display( '404',
                     new ArrayObject(array('message'=>"No se encuentra la accion '$action' en $controllerClassName")),
                     new ArrayObject() );
         }
      }
      
      // La accion existe, pero puede dar errores en la
      // ejecucion, devuelve 500 si da error.
      try
      {
         $model_or_command = $this->controller->{$actionMethod}();
      }
      catch (Exception $e)
      {
         // No existe la accion o cualquier otra excepcion que pueda tirar
         // Tira 500: Internal Server Error
         $model_or_command = ViewCommand::display( '500',
                               new ArrayObject(array('message'=>$e->getMessage(), 'traceString'=>$e->getTraceAsString(), 'trace'=>$e->getTrace(), 'exception'=>$e)),
                               new ArrayObject() );
      }
      
      //Logger::struct( $model_or_command, "MODEL OR COMMAND, " . __FILE__ . " " . __LINE__ );
      
      // ======================================================================================
      // Procesa model_or_command para devolver siempre command
      
      // Si no verifico por null antes que por get_class, 
      // get_class(NULL) me tira error en la ultima version de PHP.
      if ( $model_or_command === NULL ) // No retorno nada
      {
         // Nombre de la vista es la accion.
         $view = $action;

         // El modelo que se devuelve es solo los params submiteados.
         return ViewCommand::display( $view, $this->controller->getParams(), $this->controller->getFlash() );
      }
      
      if ( is_array($model_or_command) ) // Si la accion del controller retorna los params en lugar de ponerlos en $this->params
      {
         // Nombre de la vista es la accion.
         $view = $action;

         $returnedParams = new ArrayObject( $model_or_command );

         // Se juntan los params con el arrray devuelto
         // Tengo que transformar getParams a array porque es ArrayObject
         $allparams = array_merge( (array)$this->controller->getParams(), $model_or_command );
         //$allparams = array_merge( (array)$app->getExecActionParams(), $model_or_command );

         // El modelo que se devuelve es solo los params submiteados.
         // Tengo que transformar allParams a ArrayObject porque es lo que espera el metodo display()
         return ViewCommand::display( $view, new ArrayObject($allparams), $this->controller->getFlash() );
      }
      
      if ( get_class( $model_or_command ) === 'ViewCommand' ) // Es comando (FIXME: no es lo mismo que instanceof?)
      {
         return $model_or_command;
      }
   
      // El controlador devuelve otra cosa que no es null, ni array ni un comando,
      // el programador cometio un error y no siguio las convensiones.
      return ViewCommand::display( '500',
               new ArrayObject( array('message'=>'Error: verifique lo que retorna de la accion: '. $controller.'::'.$action .'. Solo puede devolver null, array asociativo con modelo o un comando render/redirect/renderString/renderTemplate')),
               new ArrayObject() );
   }
   
   /**
    * Devuelve los params acumulados luego de llamar a execAction.
    */
   /*
   public function getExecActionParams()
   {
      if ($this->controller != null) return $this->controller->getParams();
      return array();
   }
   public function getExecActionFlash()
   {
      if ($this->controller != null) return $this->controller->getFlash();
      return array();
   }
   */
   
   /**
    * Devuelve true si tiene bootstrap, false en caso contrario.
    */
   public function hasBootstrap()
   {
      $path2BS = 'apps/'.$this->name.'/bootstrap';
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
   
   /**
    * Obtiene el descriptor XML de la aplicacion.
    */
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
   
   /**
    * Crea una nueva aplicacion con nombre $name.
    * En $params se pasarian parametros extra como clases del
    * modelo y sus campos para crear una estructura mas rica.
    * Este parametro todavia no se usa.
    * Tira una excepcion si no puede crear la estructura de directorios.
    */
   public static function create( $name, $params = array() )
   {
      $normalizedName = String::toUnderscore( String::filterCharacters( $name ) );
      $appStruct = array(
         './apps/'.$normalizedName,
         './apps/'.$normalizedName.'/controllers',
         './apps/'.$normalizedName.'/model',
         './apps/'.$normalizedName.'/views',
         './apps/'.$normalizedName.'/services',
         './apps/'.$normalizedName.'/i18n',
         './apps/'.$normalizedName.'/bootstrap',
         './apps/'.$normalizedName.'/config',
         './apps/'.$normalizedName.'/utils'
         // TODO: filters & mappings
      );
      
      foreach ($appStruct as $package)
      {
         //echo "intenta crear $package<br/>";
         if (!file_exists($package) && !mkdir($package)) //mkdir($package, 0777, true)
         {
            throw new Exception('No se puede crear el directorio '. $package .', verifique que tiene los permisos suficientes');
         }
      }
      
      // FIXME: pueden venir tildes y cualquier tipo de caracter, deberia filtrar todo lo que venga mal, o pedirle que lo corrija.
      // TODO: crear descriptor con el nombre de la app
      $appDescriptor = FileSystem::read('./core/app/templates/app.xml');
      $appDescriptor = str_replace('{appName}', $name, $appDescriptor);
      
      if (isset($params['description']))
        $appDescriptor = str_replace('{appDescription}', $params['description'], $appDescriptor);
        
      if (isset($params['langs']))
        $appDescriptor = str_replace('{appLangs}', $params['langs'], $appDescriptor);
       
       if (isset($params['controller']))
       {
          $appDescriptor = str_replace('{epController}', $params['controller'], $appDescriptor);
          $appDescriptor = str_replace('{epAction}', 'index', $appDescriptor);
          
          $templateController = FileSystem::read('./core/app/templates/TemplateController.class.php');
          $templateController = str_replace('Template', String::firstToUpper($params['controller']), $templateController);
          
          echo './apps/'.$normalizedName.'/controllers/apps.'.$normalizedName.'.controllers.'.String::firstToUpper($params['controller']).'Controller.class.php';
          
          FileSystem::write('./apps/'.$normalizedName.'/controllers/apps.'.$normalizedName.'.controllers.'.String::firstToUpper($params['controller']).'Controller.class.php',
                            $templateController);
       }
       
       
       FileSystem::write('./apps/'.$normalizedName.'/app.xml', $appDescriptor);
   } 
   
   public function hasTests()
   {
      // FIXME: que exista el directorio no quiere decir que tenga tests.
      return in_array('tests', $this->getPackages());
   }
   
   /**
    * Genera una TestSuite con los TestCase definidos para esta aplicacion.
    */
   public function loadTests()
   {
      $dir = dir($this->path.'/tests');
      
      $testCases = array();
      
      // Recorre directorio de la aplicacion
      while (false !== ($test = $dir->read()))
      {
         //echo $test.'<br/>';
         // Se queda solo con los nombres de los directorios
         if (is_file($this->path.'/tests/'.$test) && String::endsWith($test, 'class.php'))
         {
            $testCases[] = $test;
         }
      }
      
      // Crea instancias de las clases testcase
      $suite = new TestSuite();
      foreach ($testCases as $testCaseFile)
      {
         $fi = FileNames::getFilenameInfo($testCaseFile);
         $clazz = $fi['name'];
         //print_r($fi);
         
         YuppLoader::load($fi['package'], $clazz);
         
         $suite->addTestCase( new $clazz( $suite ) );
      }
      
      return $suite;
   }
}
?>