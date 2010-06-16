<?php
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

      return $this->render("index");
      
	} // index
   
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
      
   	return $this->redirect( array( "action" => "index" ));
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

		//$includePath = $_base_dir . "/" . $type . "/" . $name; // name viene con la extension del archivo.
		$includePath = "./" . $type . "/" . $name;
		//echo $includePath;

		//$fileContent = FileSystem::read( $includePath );
		//echo $fileContent;

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
		//return $this->render($id, & $this->params);
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
//		$this->params['mode'] = "show"; // Para saber que pagina es.

//	   return $this->render(NULL, & $this->params); // Id NULL para paginas de scaffolding
      //return $this->render("show", & $this->params); // Id NULL para paginas de scaffolding
      return $this->render("show");
	}

	public function editAction()
	{
		$id = $this->params['id'];
		$clazz = $this->params['class'];

		// La clase debe estar cargada...
		eval ('$obj' . " = $clazz::get( $id );");

		$this->params['object'] = $obj;
//		$this->params['mode'] = "edit"; // Para saber que pagina es.

//      return $this->render(NULL, & $this->params); // Id NULL para paginas de scaffolding
      //return $this->render("edit", & $this->params); // Id NULL para paginas de scaffolding
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
//       $this->params['mode'] = "edit"; // Para saber que pagina es.
//       return ViewCommand :: display(NULL, & $this->params);
         //return ViewCommand :: display("edit", & $this->params);
         return $this->render("edit");
		}

		// show
		$this->params['object'] = $obj;
//		$this->params['mode'] = "show"; // Para saber que pagina es.
//		return $this->render(NULL, & $this->params);
      //return $this->render("show", & $this->params);
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
				//$this->params['mode'] = "create"; // Para saber que pagina es.
				//return $this->render(NULL, $this->params);
            //return $this->render("create", $this->params);
            return $this->render("create");
			}

			// show
			$this->params['object'] = $obj;
			//$this->params['mode'] = "show"; // Para saber que pagina es.
			//return $this->render(NULL, $this->params);
         //return $this->render("show", $this->params);
         return $this->render("show");
		}

		// create
		$this->params['object'] = $obj;
		//$this->params['mode'] = "create"; // Para saber que pagina es.
		//return $this->render(NULL, $this->params);
      //return $this->render("create", $this->params);
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
      Logger::show("Execute Bootstrap Action");
      
  	   $component = $this->params['componentName'];
      
      //ob_start();
      
      // importa derecho la pagina...
      //include_once( $pagePath );
      
      YuppLoader::getInstance()->loadScript("components.".$component, "Bootstrap");
      
      //$view = ob_get_clean();
      
      return $this->redirect( array('action' => 'index'));
   }
   
   
   public function showStatsAction()
   {
      YuppLoader::load("core.utils", "YuppStats");
      $stats = new YuppStats();
      $stats = $stats->showStats();
      
      return $this->renderString( $stats );
   }
}
?>