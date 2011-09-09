<?php

YuppLoader::load('core.app','App');
YuppLoader::load('core','Yupp');
YuppLoader :: load('core.mvc', 'YuppController');

class CoreController extends YuppController {

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

      return $this->render("index");
      
   } // index
   
   // FIXME: auxiliar para daState2, deberia estar en basic/array
   function array_flatten($a)
   {
      if (count($a) == 0) return $a; // Ojo: sin esto falla para $a=array()
      foreach($a as $k=>$v) $a[$k] = (array)$v;
      return call_user_func_array('array_merge', $a);
   }
   
   // dbStatus2
   public function dbStatusAction()
   {
      $yupp = new Yupp();
      $appNames = $yupp->getAppNames();
      $appModelClasses = array(); // [appName][class][tablename,creada o no]
      
      // Incluye todas las clases del modelo de todas las apps
      YuppLoader :: loadModel();
      
      // Para saber si se crearon las tablas para todas las
      // clases del modelo de todas las aplicaciones.
      $allTablesCreated = true;
      
      $fn = new FileNames();
      
      foreach ($appNames as $appName)
      {
         $app = new App($appName);
         $modelClassFileNames = $app->getModel(); // no incluye las clases, solo obtiene los nombres
         
         //print_r($modelClassFileNames);
         
         // Necesito que sea plano el array, si no, tengo que hacer recorrida recursiva.
         // Esto no seria necesario si modifico la recorrida en la vista, para mostrar
         // la estructura interna de paquetes del modelo de la aplicacion.
         $modelClassFileNames = $this->array_flatten($modelClassFileNames);
         
         //print_r($modelClassFileNames);
         

         // Toda la informacion de las clases y tablas creadas para esta app
         $appModelClasses[$appName] = array();
         
         $dal = new DAL($appName);
         
         foreach ($modelClassFileNames as $classFileName)
         {
            $fileInfo = $fn->getFileNameInfo($classFileName);
            $className = $fileInfo['name'];
            
            // Para incluir las clases (por si no estan incluidas)
            // Ticket: http://code.google.com/p/yupp/issues/detail?id=71
            YuppLoader::load($fileInfo['package'], $className);
            
            $tableName = YuppConventions::tableName( $className );
            if ( $dal->tableExists( $tableName ) )
            {
               $appModelClasses[$appName][$className] = array('tableName'=>$tableName, 'created'=>"CREADA");
            }
            else
            {
               $appModelClasses[$appName][$className] = array('tableName'=>$tableName, 'created'=>"NO CREADA");
               $allTablesCreated = false;
            }
         }
      }
      
      $this->params['allTablesCreated'] = $allTablesCreated;
      $this->params['appModelClasses'] = $appModelClasses;
      
      return $this->render("dbStatus");
   }
   // dbStatus2
   
   
   /**
    * Sirve para listar los controladores de una app cuando se ingresa la URL hasta la app.
    * TODO: en produccion no se deberia mostrar esta lista, tampoco si hay algun mapeo predefinido que matchee con la url dada.
    */
   public function appControllersAction()
   {
      // FIX: http://code.google.com/p/yupp/issues/detail?id=121
      if (!Yupp::appExists($this->params['app']))
      {
         $this->params['message'] = 'Verifique que la aplicacion <b>'. $this->params['app'] .'</b> existe';
         return $this->render('404');
      }
      return;
   }
   
   /**
    * Accion para generar las tablas para guardar el modelo.
    */
   public function createModelTablesAction()
   {
      // Incluye todas las clases del modelo de todas las apps
      YuppLoader :: loadModel();
      
      // TODO: si genera errores se deberian mostrar lindos, 
      // ahora me muestra unas excepciones de las consultas 
      // a la DB para las tablas que ya existen que no se pueden crear.
      //echo "PM generateAll<br/>";
      PersistentManager::getInstance()->generateAll();
      
      $this->flash['message'] = 'Generaci&oacute;n de tablas completada.';
      
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
      $app = $this->params['app'];
      $clazz = $this->params['class'];

      // paginacion
      if (!isset($this->params['max']))
      {
         $this->params['max'] = 10;
         $this->params['offset'] = 0;
      }
      
      // Tengo que asegurarme de que cargue la config de DB para esa aplicacion
      // Asi en lugar de obtener 'core' en la app, obtiene el nombre de la apliciacion real cuando crea la DAL en PM.
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $app );
      
      // Verifica que la clase esta cargada, si no, carga todo (porque no se de que aplicacion es)
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($clazz, $loadedClasses)) YuppLoader::loadModel();

      eval ('$list = ' . $clazz . '::listAll( $this->params );'); // Se pasan los params por si vienen atributos de paginacion.
      $this->params['list'] = $list;

      eval ('$count = ' . $clazz . '::count();');
      $this->params['count'] = $count; // Maximo valor para el paginador.

      return $this->render("list");
   }
   
   /**
    * Listado de objetos relacionados por hasMany a otro objeto.
    * id: identificador del objeto padre
    * class: clase del objeto padre
    * attr: nombre del atributo hasmany en el objeto padre
    * refclass: clase de los objetos hasmany
    */
   public function listManyAction()
   {
      $app = $this->params['app'];
      $class = $this->params['class'];
      $id = $this->params['id'];
      $attr = $this->params['attr'];
      $refclass = $this->params['refclass'];
      
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $app );
      
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($class, $loadedClasses)) YuppLoader::loadModel();
      
      eval ('$obj = '. $class .'::get('.$id.');');
      $list = $obj->aGet($attr);
      
      return $this->renderTemplate('../list', array('list'=>$list, 'class'=>$refclass));
   }

   // FIXME: misma accion implementada en YuppController, lo unico distinto es como saca el nombre de la clase a mostrar.
   public function showAction()
   {
      $app = $this->params['app'];
      $clazz = $this->params['class'];
      $id = $this->params['id'];
      
      // Tengo que asegurarme de que cargue la config de DB para esa aplicacion
      // Asi en lugar de obtener 'core' en la app, obtiene el nombre de la apliciacion real cuando crea la DAL en PM.
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $app );
      
      
      // Verifica que la clase esta cargada, si no, carga todo (porque no se de que aplicacion es)
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($clazz, $loadedClasses))
         YuppLoader::loadModel();
      
      //print_r($loadedClasses);
      //print_r(get_declared_classes());
      
      // La clase debe estar cargada...
      eval ('$obj' . " = $clazz::get( $id );");

      $this->params['object'] = $obj;

      return $this->render("show");
   }

   public function editAction()
   {
      $id = $this->params['id'];
      $clazz = $this->params['class'];
      $appName = $this->params['app'];
      
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($clazz, $loadedClasses))
         YuppLoader::loadModel();

      // Para que cargue la configuracion correcta de la base de datos.
      // Si no trata de ejecutar usando la configuracion de la base por defecto.      
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $appName );

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
      $appName = $this->params['app'];
      
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($clazz, $loadedClasses))
         YuppLoader::loadModel();
      
      // Para que cargue la configuracion correcta de la base de datos.
      // Si no trata de ejecutar usando la configuracion de la base por defecto.      
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $appName );
      
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
      $appName = $this->params['app'];
      
      // Para que cargue la configuracion correcta de la base de datos.
      // Si no trata de ejecutar usando la configuracion de la base por defecto.      
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $appName );
      
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($clazz, $loadedClasses))
         YuppLoader::loadModel();
      
      eval ('$ins' . " = $clazz::get( $id );");
      $ins->delete();

      $this->flash['message'] = "Elemento [$clazz:$id] eliminado.";

      return $this->redirect( array("action" => "list", "params"=>array("app"=>$appName, "class"=>$clazz) ) );
   }

   /**
    * Accion para crear una nueva instancia de la clase pasada como parametro.
    * Sirve cuando la accion no esta definida en el controller o mismo no hay definido un controller para la clase.
    */
   public function createAction()
   {
      $clazz = $this->params['class'];
      
      $loadedClasses = get_declared_classes(); //YuppLoader::getLoadedModelClasses(); // FIXME: Tengo clases cargadas en YuppLoader pero no estan incluidas (debe ser por persistencia del singleton en session)
      if (!in_array($clazz, $loadedClasses))
         YuppLoader::loadModel();
      
      
      $obj = new $clazz (); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.
      $appName = $this->params['app'];
      
      // Para que cargue la configuracion correcta de la base de datos.
      // Si no trata de ejecutar usando la configuracion de la base por defecto.      
      $ctx = YuppContext::getInstance();
      $ctx->setRealApp( $appName );
      
      // View create, que es como edit pero la accion de salvar vuelve aqui.

      if (isset($this->params['doit'])) // create
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
      $app = $this->params['back_app'];
      $controller = $this->params['back_controller'];
      $action = $this->params['back_action'];

      $ctx = YuppContext :: getInstance();
      $ctx->setLocale($locale);
      //$ctx->update(); // FIXME: al sacar que CTX fuera un Singleton Persistente se rompio recordar el idioma seleccionado. Eso deberia estar en sesion.

      // Vuelvo a donde estaba...
      return $this->redirect(array (
         'app' => $app,
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
      $app = $this->params['back_app'];
      $controller = $this->params['back_controller'];
      $action = $this->params['back_action'];

      $ctx = YuppContext :: getInstance();
      $ctx->setMode($mode);
      //$ctx->update();

      // Vuelvo a donde estaba...
      return $this->redirect(array (
         'app' => $app,
         'controller' => $controller,
         'action' => $action
      ));
   }

   /**
    * Ejecuta el boostrap de una app dado.
    */
   public function executeBootstrapAction()
   {
      Logger::show('Execute Bootstrap Action');
      
      $appName = $this->params['appName'];
      
      //ob_start();
      
      // Para que cargue la configuracion correcta de la base de datos.
      // Si no trata de ejecutar usando la configuracion de la base por defecto.
      
      $ctx = YuppContext::getInstance();
      $ctx->setApp( $appName );

      // FIXME: el BS a ejecutar debe depender del modo de ejecucion
      YuppLoader::getInstance()->loadScript('apps.'.$appName.'.bootstrap', 'Bootstrap');
      
      //$output = ob_get_clean();
      //FileSystem::appendLine('bootstrap_log.html', $output);
      
      $this->flash['message'] = 'Ejecuci&oacute;n de bootstrap completada.';
      
      return $this->redirect( array('app'=>'core', 'controller'=>'core', 'action'=>'index'));
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
    * in: controller
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
      
      // Si hay tests
      if (!$app->hasTests())
      {
         return $this->redirect( array('action' => 'index',
                                       'params'=>array('flash.message'=>'No hay tests para ejecutar')));
      }

      // Para que cargue la configuracion correcta de la base de datos.
      // Si no trata de ejecutar usando la configuracion de la base por defecto.      
      $ctx = YuppContext::getInstance();
      $ctx->setApp( $appName );
      
      // Cargar los casos de test en una suite y ejecutarlos
      $suite = $app->loadTests();
      $suite->run();
      
      //print_r( $suite->getReports() );
      
      $this->params['results'] = $suite->getReports();
      $this->params['app'] = $app;
   }
   
   public function getNewsFromTwitterAction()
   {
      YuppLoader::load('core.http', 'HTTPRequest');
      
      $req = new HTTPRequest();
      $req->setTimeOut(20);
      $res = $req->HTTPRequestGet('http://api.twitter.com/1/statuses/user_timeline.json?screen_name=ppazos&trim_user=1');
      
      //print_r($req);
      //print_r($res);
      
      if ($res->getStatus() == '200')
      {
          $json = $res->getBody();
      }
      else
      {
          $json = '[]';
      }
      
      header('Content-Type: application/json');
      return $this->renderString($json);
   }
}
?>