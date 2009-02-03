<?php

//chdir('core');
include_once ('./core/utils/core.utils.ModelUtils.class.php');
include_once ('./core/config/core.config.FileNames.class.php');
include_once ('./core/config/core.config.PackageNames.class.php');
//chdir('..');

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
      $packageNames = new PackageNames();
      foreach( $this->loadedClasses as $fileInfo )
      {
      	if ( $packageNames->isModelPackage( $fileInfo['package'] ) )
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
	public function _loadModel()
	{
      //echo "<h1>" . __FILE__ . " (". __LINE__ .") _loadModel</h1>";
      
      
      $components = FileSystem::getSubdirNames("./components");
      
      /*
      print_r( FileSystem::getSubdirNames("./components") );
      foreach ($components as $component)
      {
      	echo YuppConventions::getModelPath($component) . "<br/>";
         if (file_exists(YuppConventions::getModelPath($component))) echo "EXISTE<br/>";
         else echo "NO EXISTE<br/>";
      }
      */
      
      
		$packs = new PackageNames();
		//$path = $packs->getModelPackagePath(); // ./model
		//$dir = dir($path);

		$fn = new FileNames();

		if (!$this->modelLoaded)
		{
			// FIXME: Si el modelo ya esta cargado no deberia leer el disco para cargarlo, deberia fijarme la estructura que tengo en memoria y hacer el include de eso de nuevo...
			// Esto es porque la lectura de disco tarda pila, y eso que hay pocas clases.
         /*
			while (false !== ($entry = $dir->read()))
			{
				if ($entry != "." && $entry != "..")
				{
					$finfo = $fn->getFilenameInfo($entry);
					if ($finfo)
					{
						//print_r( $finfo );

						//echo "PACKAGE: " . $finfo['package'] . "</br>";
						//echo "NAME: "    . $finfo['name'] . "</br>";

						$this->_load($finfo['package'], $finfo['name']);

						//echo $entry."\n";
					}
				}
			}
			$dir->close();
         */
         
         // Carga: component/elComponent/model, para todos los componentes
         foreach ($components as $component)
         {
            //echo "<h1>" . YuppConventions::getModelPath($component) . "</h1><br/>";
            $path = YuppConventions::getModelPath($component);
            if (file_exists($path))
            {
               $dir = dir($path);
               while (false !== ($entry = $dir->read()))
               {
                  //echo "ENTRY: $entry<br/>";
                  if ($entry != "." && $entry != "..")
                  {
                     $finfo = $fn->getFilenameInfo($entry);
                     if ($finfo)
                     {
                        //print_r( $finfo );
      
                        //echo "PACKAGE: " . $finfo['package'] . "</br>";
                        //echo "NAME: "    . $finfo['name'] . "</br>";
                        //echo "<br/>LOAD (1)<br/>";
                        
                        // TODO: cargar una clase podria cargar otras, si se declaran loads en esa clase,
                        //       por lo que estaria bueno poder verificar aqui si la clase ya esta cargada 
                        //       antes de intentar cargarla de nuevo.
                        $this->_load($finfo['package'], $finfo['name']);
                        //echo "LOAD (2)<br/>";
      
                        //echo $entry."\n";
                     }
                  }
               }
               $dir->close();
            }
         }

			$this->modelLoaded = true;
			// necesaria para mantener actualizada la session con la instance del singleton. (xq no referencia a la session xa este es un valor desserealizado...)
			YuppSession :: set("_class_loader_singleton_instance", $this); // actualizo la variable en la session...
         
         //echo "<h2>" . __FILE__ . " (". __LINE__ .") ACTUALIZA CLASS LOADER EN SESSION</h2>";
		}
		else
		{
         //echo "<h2>" . __FILE__ . " (". __LINE__ .") REFRESH</h2>";
			self :: refresh();
		}
      
      //echo "<h1>" . __FILE__ . " (". __LINE__ .") _loadModel TERMINA</h1>";
      
	} // _loadModel

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

		$fn = new FileNames();
		$filename = $fn->getClassFilename($package, $clazz);

		//echo "FILE $filename<br />";

		// tengo que ver de que tipo es para pedir la ruta correcta...
		// el que sabe la ruta es PackageNames ...
		//
		$path = ".";
		$packs = new PackageNames();
		if ($packs->isModelPackage($package))
		{
         $component = $packs->getModelPackageComponent( $package );
         
//			echo "<br/>ES MODEL PACKAGE!!! $package, $clazz, compo: $component<br/>";
			//$path = $packs->getModelPackagePath(); // FIXME: ahora el modelo depende del componente.
         $path = YuppConventions::getModelPath($component);
         
//         echo "Path: $path<br/>";
		}
		else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
		{
			$path = strtr($package, ".", "/");
		}
		// ... else demas...

		$incPath = $path . "/" . $filename;

		//echo $incPath . "<br />";
      //echo "<h3>" . __FILE__ . " (". __LINE__ .") INC PATH: $incPath</h3>";

		if (!is_file($incPath))
			throw new Exception("YuppLoader::load() - ruta de inclusion errada ($incPath)");

		//    echo "INC: $incPath <br/>";
		include_once ($incPath); // esto lo tengo que hacer aunque ya tenga la clase registrada xq si no php no se da cuenta que tiene que incluirla...

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

		$fn = new FileNames();
		$filename = $fn->getInterfaceFilename($package, $interface);

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

	// Script
	public static function loadScript($package, $script)
	{
		$cl = YuppLoader :: getInstance();
		$cl->_loadScript($package, $script);
	}

	// MISMA LOGICA QUE _load... habra que reusar codigo...
	private function _loadScript($package, $script)
	{
		// Tengo que armar el nombre del archvo desde el nombre del paquete y la clase, ademas tengo que ver la path en la config.
		$fn = new FileNames();
		$filename = $fn->getScriptFilename($package, $script);

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
	// /Script

	public static function isLoadedClass($package, $clazz)
	{
		$cl = YuppLoader :: getInstance();
		return $cl->_isLoadedClass($package, $clazz);
	}

	public function _isLoadedClass($package, $clazz)
	{
		// IDEM A LOAD...
		$fn = new FileNames();
		$filename = $fn->getClassFilename($package, $clazz);

		$path = ".";
		$packs = new PackageNames();
		if ($packs->isModelPackage($package))
		{
			//echo "ES MODEL PACKAGE!!!<br/>";
			//$path = $packs->getModelPackagePath();
         $path = YuppConventions::getModelPath( getModelPackageComponent( $package ) );
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
	 */
	public static function refresh()
	{
		$cl = YuppLoader :: getInstance();

		foreach ($cl->loadedClasses as $classInfo)
		{
			$package = $classInfo['package'];
			//$incPath = $package . "/" . $classInfo['filename']; // Comun
			$path = ".";
			$packs = new PackageNames();
			if ($packs->isModelPackage($package))
			{
				$path = YuppConventions::getModelPath($packs->getModelPackageComponent( $package ));
			}
			else // trata de armar la ruta con el paquete, este es el caso en q el paquete fisico sea igual que el logico.
				{
				$path = strtr($package, ".", "/");
			}
			$incPath = $path . "/" . $classInfo['filename'];

			if (!is_file($incPath))
				throw new Exception("YuppLoader::refresh() - ruta de inclusion errada ($incPath)");

			//    echo "INCLUDE: $incPath <br/>";
			include_once ($incPath);
		}
	}

}
?>