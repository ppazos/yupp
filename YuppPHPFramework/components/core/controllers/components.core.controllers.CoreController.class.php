<?php

YuppLoader::load('core','App');
YuppLoader::load('core','Yupp');

class CoreController extends YuppController
{

	/**
	 * Accion que se llama por defecto al ingresar sin un nombre de accion.
	 */
	public function indexAction()
	{
		// Si estoy en mode DEV quiero mosrar informacion sobre lo 
		// que hay en la base, y lo que falta crear, y dar opcion 
		// a que genere las tablas desde la vista.
      
      // Test Yupp Desktop
      $yupp = new Yupp();
      $appNames = $yupp->getAppNames();
      $apps = array();
      foreach ($appNames as $name)
      {
         $apps[] = new App($name);
      }
      $this->params['apps'] = $apps;
      // /Test Yupp Desktop 

/*
      $dal = DAL::getInstance();
		if (YuppContext :: getInstance()->getMode() === YuppConfig :: MODE_DEV)
		{
         $createdTables = array(); // array de clase / array tabla / creada o no creada.
         $allTablesCreated = true;
         
			$loadedClasses = YuppLoader :: getLoadedModelClasses();
			$this->params['loadedClasses'] = $loadedClasses;

			foreach ($loadedClasses as $class)
			{
            $tableName = YuppConventions::tableName( $class );
            if ( $dal->tableExists( $tableName ) )
            {
            	$createdTables[$class] = array('tableName'=>$tableName, 'created'=>"CREADA");
            }
            else
            {
            	$createdTables[$class] = array('tableName'=>$tableName, 'created'=>"NO CREADA");
               $allTablesCreated = false;
            }
			}
         
         //$this->params['modelTables']      = $createdTables;
         $this->params['allTablesCreated'] = $allTablesCreated;
		}
      
      
      // Nombres de los compoentes instalados
      $components = PackageNames::getComponentNames();
      $this->params['components'] = $components;
      
      $componentModelClasses = array();
      foreach ($components as $component)
      {
         $classes = ModelUtils::getModelClasses($component);
         foreach ($classes as $class)
         {
            $fileInfo = FileNames::getFilenameInfo( $class );
            $componentModelClasses[$component][$fileInfo['name']] = $createdTables[$fileInfo['name']];
         }
      }
      
      //print_r( $componentModelClasses );
      
      $this->params['componentModelClasses'] = $componentModelClasses;
*/

      return $this->render("index");
      
	} // index
   
   
   /**
    * Para ver el modelo de todos los componentes y si estan creadas las tablas.
    * Copio parte del codigo del viejo indexAction.
    */
   public function dbStatusAction()
   {
      $dal = DAL::getInstance();
      if (YuppContext :: getInstance()->getMode() === YuppConfig :: MODE_DEV)
      {
         $createdTables = array(); // array de clase / array tabla / creada o no creada.
         $allTablesCreated = true;
         
         $loadedClasses = YuppLoader :: getLoadedModelClasses();
         $this->params['loadedClasses'] = $loadedClasses;

         foreach ($loadedClasses as $class)
         {
            $tableName = YuppConventions::tableName( $class );
            if ( $dal->tableExists( $tableName ) )
            {
               $createdTables[$class] = array('tableName'=>$tableName, 'created'=>"CREADA");
            }
            else
            {
               $createdTables[$class] = array('tableName'=>$tableName, 'created'=>"NO CREADA");
               $allTablesCreated = false;
            }
         }
         
         //$this->params['modelTables']      = $createdTables;
         $this->params['allTablesCreated'] = $allTablesCreated;
      }
      
      // Nombres de los compoentes instalados
      $components = PackageNames::getComponentNames();
      $this->params['components'] = $components;
      
      $componentModelClasses = array();
      foreach ($components as $component)
      {
         $classes = ModelUtils::getModelClasses($component);
         /*
         if ( !isset($componentModelClasses[$component]) )
         {
            $componentModelClasses[$component] = array();
         }
         */
         foreach ($classes as $class)
         {
            $fileInfo = FileNames::getFilenameInfo( $class );
            $componentModelClasses[$component][$fileInfo['name']] = $createdTables[$fileInfo['name']];
         }
      }
      
      //print_r( $componentModelClasses );
      
      $this->params['componentModelClasses'] = $componentModelClasses;
      
      return $this->render("dbStatus");
   }
   
   
   /**
    * Sirve para listar los controladores de un componente cuando se ingresa la URL hasta el componente.
    * TODO: en produccion no se deberia mostrar esta lista, tampoco si hay algun mapeo predefinido que matchee con la url dada.
    */
   public function componentControllersAction()
   {
      return;
   }
   
   
   /**
    * Accion para generar las tablas para guardar el modelo.
    */
   public function createModelTablesAction()
   {
      // TODO: si genera errores se deberian mostrar lindos, 
      // ahora me muestra unas excepciones de las consultas 
      // a la DB para las tablas que ya existen que no se pueden crear.
      PersistentManager::getInstance()->generateAll();
      
   	return $this->redirect( array( 'action' => 'dbStatus' ));
   }


	/**
    * FIXME: Esta no se necesita mas, esto fue resuelto en el htaccess para poder acceder a los archivos fisicos. 
    * 
	 * @param $type tipo de recurso: js, css, img.
	 * @param $name nombre del recurso.
	 * @return el recurso pedido como stream.
	 */
	public function staticResourceAction()
	{
		global $_base_dir;

		$type = $this->params['type'];
		$name = $this->params['name'];

		$includePath = "./" . $type . "/" . $name; // name viene con la extension del archivo.

		if (file_exists($includePath))
		{
			if ($type === "css")
				header('Content-Type: text/css;');
			else
				if ($type === "js")
					header('Content-Type: text/javascript;');
			//else if ( $type === "css" )
			//   header('Content-Type: text/css;'); // TODO: image/gif, image/jpeg, image/png, 

			header("Content-Length: " . filesize($includePath));

			@ readfile($includePath);
		}
	}

	/**
	 * FIXME: esta donde se usa?
    * Accion estandar para mostrar una pagina.
	 */
	public function displayAction()
	{
		$id = $this->params['_param_1'];
		$this->flash['message'] = "Arriba loco, este es el mensaje del flash!";
      return $this->render( $id );
	}

   
	/**
	 * Mostrar lista de elementos de alguna clase.
	 */
   public function listAction()
	{
		$clazz = $this->params['class'];

		// paginacion
		if (!$this->params['max'])
		{
			$this->params['max'] = 10;
			$this->params['offset'] = 0;
		}

		eval ('$list = ' . $clazz . '::listAll( $this->params );'); // Se pasan los params por si vienen atributos de paginacion.
		$this->params['list'] = $list;

		eval ('$count = ' . $clazz . '::count();');
		$this->params['count'] = $count; // Maximo valor para el paginador.

      //return $this->render("list", & $this->params); // Id NULL para paginas de scaffolding
      return $this->render("list");
	}
   

	public function showAction()
	{
		$id = $this->params['id'];
		$clazz = $this->params['class'];

		// La clase debe estar cargada...
		eval ('$obj' . " = $clazz::get( $id );");

		$this->params['object'] = $obj;

      return $this->render("show");
	}

	public function editAction()
	{
		$id = $this->params['id'];
		$clazz = $this->params['class'];

		// La clase debe estar cargada...
		eval ('$obj' . " = $clazz::get( $id );");

		$this->params['object'] = $obj;

      return $this->render("edit");
	}

	/**
	 * llamada desde el edit para salvar modificaciones.
	 */
	public function saveAction()
	{
		$id = $this->params['id'];
		$clazz = $this->params['class']; // Lo necesito porque no puedo saber por el nombre del controller!
		eval ('$obj' . " = $clazz::get( $id );");
		$obj->setProperties($this->params);

		if (!$obj->save()) // Con validacion de datos!
		{
			// create
			$this->params['object'] = $obj;
         return $this->render("edit");
		}

		// show
		$this->params['object'] = $obj;
      return $this->render("show");
	}

	public function deleteAction()
	{
		$id = $this->params['id'];
		$clazz = $this->params['class']; // Lo necesito porque no puedo saber por el nombre del controller!
		eval ('$ins' . " = $clazz::get( $id );");
		$ins->delete();

		$this->flash['message'] = "Elemento [$clazz:$id] eliminado.";

		return $this->redirect( array("action" => "list") );
	}

   /**
    * Accion para crear una nueva instancia de la clase pasada como parametro.
    * Sirve cuando la accion no esta definida en el controller o mismo no hay definido un controller para la clase.
    */
	public function createAction()
	{
		$clazz = $this->params['class'];
		$obj = new $clazz (); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

		// View create, que es como edit pero la accion de salvar vuelve aqui.

		if ($this->params['doit']) // create
		{
			$obj->setProperties($this->params);
			if (!$obj->save()) // Con validacion de datos!
			{
				// create
				$this->params['object'] = $obj;
            return $this->render("create");
			}

			// show
			$this->params['object'] = $obj;
         return $this->render("show");
		}

		// create
		$this->params['object'] = $obj;
      return $this->render("create");
	}
   

	/**
	 * Accion para cambiar el locale.
	 */
	public function changeLocaleAction()
	{
		$locale = $this->params['locale'];
		$component = $this->params['back_component'];
		$controller = $this->params['back_controller'];
		$action = $this->params['back_action'];

		$ctx = YuppContext :: getInstance();
		$ctx->setLocale($locale);
		$ctx->update();

		// Vuelvo a donde estaba...
		return $this->redirect(array (
			'component' => $component,
			'controller' => $controller,
			'action' => $action
		));
	}

	/**
	 * Accion para cambiar el modo de ejecucion.
	 */
	public function changeModeAction()
	{
		$mode = $this->params['mode'];
		$component = $this->params['back_component'];
		$controller = $this->params['back_controller'];
		$action = $this->params['back_action'];

		$ctx = YuppContext :: getInstance();
		$ctx->setMode($mode);
		$ctx->update();

		// Vuelvo a donde estaba...
		return $this->redirect(array (
			'component' => $component,
			'controller' => $controller,
			'action' => $action
		));
	}


   /**
    * Ejecuta el boostrap de un componente dado.
    */
   public function executeBootstrapAction()
   {
      Logger::show('Execute Bootstrap Action');
      
  	   $component = $this->params['componentName'];
      
      //ob_start();
      
      // importa derecho la pagina...
      //include_once( $pagePath );
      
      // FIXME: el BS a ejecutar debe depender del modo de ejecucion
      YuppLoader::getInstance()->loadScript('components.'.$component.'.bootstrap', 'Bootstrap');
      
      //$view = ob_get_clean();
      
      return $this->redirect( array('action' => 'index'));
   }
   
   
   public function showStatsAction()
   {
      YuppLoader::load('core.utils', 'YuppStats');
      $stats = new YuppStats();
      $stats = $stats->showStats();
      
      return $this->renderString( $stats );
   }
   
   /**
    * in: name 1..1
    * in: description
    * in: langs
    */
   public function createAppAction()
   {
      if (isset($this->params['doit']))
      {
         if (!isset($this->params['name']))
         {
            $this->flash['message'] = 'El nombre de la aplicacion es obligatorio';
            return;
         }
         
         App::create( $this->params['name'], (array)$this->params );
         
         return $this->redirect( array('action' => 'index'));
      }
   }
   
   /**
    * Para ejecutar los tests de una aplicacion.
    * in: name 1..1 nombre de la aplicacion
    */
   public function testAppAction()
   {
      YuppLoader::load('core.testing', 'TestSuite');
      YuppLoader::load('core.testing', 'TestCase');
      $appName = $this->params['name'];
      $app = new App($appName);
      
      //print_r($app->getPackages());
      
      // TODO: App.hasTests
      if (!in_array('tests', $app->getPackages()))
      {
         return $this->redirect( array('action' => 'index',
                                       'params'=>array('flash.message'=>'No hay tests para ejecutar')));
      }
      
      // TODO: App.loadTests
      
      $dir = dir('./components/'.$appName.'/tests');
      
      $testCases = array();
      
      // Recorre directorio de la aplicacion
      while (false !== ($test = $dir->read()))
      {
         //echo $test.'<br/>';
         // Se queda solo con los nombres de los directorios
         if ( String::endsWith( $test, 'class.php' ) && is_file('./components/'.$appName.'/tests/'.$test))
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
      
      $suite->run();
      //print_r( $suite->getReports() );
      
      $this->params['results'] = $suite->getReports();
      $this->params['app'] = $app;
      
      /*
       * 
         $tc = new CustomTestCase($suite);
         $suite->addTestCase($tc);
         
         
       */
   } 
}
?>