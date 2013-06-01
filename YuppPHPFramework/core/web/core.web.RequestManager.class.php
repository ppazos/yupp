<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */

YuppLoader::load('core.layout', 'LayoutManager');
YuppLoader::load('core.support', 'Timer');
YuppLoader::load('core', 'Yupp');

YuppLoader :: load('core.support', 'YuppContext');
YuppLoader :: load('core.config', 'YuppConfig');
YuppLoader :: load('core.routing', 'Router');
YuppLoader :: load('core.routing', 'YuppControllerFilter');
YuppLoader :: load('core.routing', 'Executer');
YuppLoader :: load('core.mvc', 'Model');

/*
 * Implementa el proceso y control de generacion de paginas a partir del request.
 * Proceso de urls, verificacion de permisos, ejecucion de acciones, pasarle modelo al view, render del view, etc.
 */
class RequestManager {

   private function __construct() {}

   // El punto de entrada le envia el request como parametro (no accedo de forma global!)
   public static function doRequest()
   {
      global $_base_dir;

      // TODO: que el mostrar el tiempo de proceso sea configurable.
      //$tiempo_inicio = microtime(true);
      $timer_process = new Timer();
      $timer_process->start();

      // Establezco la url base, independiente del directorio donde este situado el script.
      // Si la uri es: http://localhost:8081/Persistent/test/usermanager/person/create?name=pepe&age=23&height=180
      // y este script esta en http://localhost:8081/Persistent/test/
      // Url sera: usermanager/person/create?name=pepe&age=23&height=180

      // ====================================================
      // ROUTING: el objetivo es devolver un $command
      
      $router = new Router( $_SERVER['REQUEST_URI'] );
      $lr = $router->getLogicalRoute();
      $ctx = YuppContext::getInstance();
      
      //Logger::struct( $lr, "LOGICAR ROUTE 1 " .__FILE__.' '.__LINE__ );
         
      // Verifica salida del router y setea valores que no vienen seteados.
      // TODO: OJO, luego debe pasar el verificador de si el controller
      //       y action existen, y si no, ejecutar contra core.
      
      // Esto dice a donde ir cuando se accede a la aplicacion YUPP,
      // esta bien que se haga aca, no es cosa del router.
      if ( empty($lr['app']) )
      {
         $config = YuppConfig::getInstance();
         $modeDefaultMapping = $config->getModeDefaultMapping();
         $lr['app']        = $modeDefaultMapping['app'];
         $lr['controller'] = $modeDefaultMapping['controller'];
         $lr['action']     = $modeDefaultMapping['action'];
         
         $router->addCustomParams( $modeDefaultMapping['params'] );
      }
      
      // FIXME: esto lo deberia hacer el router
      // Si la ruta en la URL llega hasta la app,
      // se muestran los controladores de la app.
      if ( empty($lr['controller']) )
      {
         /*
         if (!Yupp::appExists($lr['app']))
         {
            // Tira 404: Not Found
            $command = ViewCommand::display( '404',
                                        new ArrayObject(array('message'=>'La aplicaci&oacute;n <b>'.$lr['app'].'</b> no existe')),
                                        new ArrayObject() );
         }
         else
         {
         */
            //Logger::getInstance()->po_log("RM: ".__FILE__ .' '.__LINE__);
            
            $router->addCustomParams( array('app'=>$lr['app']) );
            $lr['app'] = "core"; // Le dice a core/core que muestre los controllers de la app $lr['app']
            $lr['controller'] = "core";
            $lr['action'] = "appControllers";
         /*
         }
         */
      }
      else // si viene la app y el controller en la url
      {
          // Prefiero el parametro por url "_action_nombreAccion", a la accion que viene en la URL (app/controlador/accion).
          // Esto es porque los formularios creados con YuppForm generan acciones distintas para botones de 
          // submit distintos y la accion es pasada codificada en un parametros _action_nombreAcction.
          
          // Por si la accion viene codificada en una key de un param como '_action_laAccion', por ejemplo: esto pasa en un submit de un YuppForm.
          $actionParam = $router->getActionParam();
          if ( empty($actionParam) )
          {
             if ( !isset($lr['action']) || $lr['action'] === "" )
             {
                $lr['action'] = 'index';
             }
          }
          else
          {
             // FIXME: hay un problema con actionParam cuando se manda desde un form.
             // La accion que aparece en la URL es la de la action del form, pero la
             // vista que se muestra es la que renderea la acction actionParam. La URL
             // deberia ser tambien la que diga actionParam. Por eso haria un redirect
             // en lugar de un render.
             $lr['action'] = $actionParam;
          }
      }
      
      
      // Si en logicalRoute se ponen parametros ej. en el AppMapping,
      // se deben agregar al router como customParams:
      if (isset($lr['params']))
      {
         $router->addCustomParams( $lr['params'] ); // Deberia ser un array...
      }
      
      
      //Logger::struct( $lr, "LOGICAR ROUTE 2 " .__FILE__.' '.__LINE__ );
           
      // *******************************************************************************
      // FIXME: puedo tener app, controlador y accion, pero pueden ser nombres
      // errados, es decir, que no existen, por ejemplo si en la url le paso /x/y/z.
      // Aqui hay que verificar si existe antes de seguir, y si la app no existe,
      // o si existe pero el controlador no existe, o si ambos existen, si la accion 
      // en el controlador no existe, deberia devolver un error y mostrarlo lindo (largar una exept).
      // Estaria bueno definir codigos estandar de errores de yupp, para poder tener una
      // lista ed todos los errores que pueden ocurrir.
      // *******************************************************************************

      // FIXME: no armar esto a mano, pedirselo a alguna clase de convensiones o la nueva clase App.
      $appPath       = "apps/".$lr['app'];
      $controllerClassName = String::firstToUpper($lr['controller']) . "Controller";
      $controllerFileName  = "apps.".$lr['app'].".controllers.".$controllerClassName.".class.php";
      $controllerPath      = "apps/".$lr['app']."/controllers/".$controllerFileName;
      
      /// ACTUALIZAR CONTEXTO ///
      $ctx->setApp( $lr['app'] );
      $ctx->setController( $lr['controller'] );
      $ctx->setAction( $lr['action'] );
      /// ACTUALIZAR CONTEXTO ///
      
      //Logger::struct( $lr, "LOGICAR ROUTE 3 " .__FILE__.' '.__LINE__ );
      //echo "<hr/>PATH: $controllerPath<br/>";
      
      // Verifico que lo que pide existe...
      if ( !file_exists($appPath) ) // FIXME: yupp::appExists
      {
         Logger::getInstance()->log("Path1 '$appPath' no existe");
        
         // Tira 404: Not Found
         $command = ViewCommand::display( '404',
                                          new ArrayObject(array('message'=>'La aplicaci&oacute;n <b>'.$lr['app'].'</b> no existe')),
                                          new ArrayObject() );
      }
      else if (!file_exists($controllerPath))
      {
         Logger::getInstance()->log("Path2 '$controllerPath' no existe");
        
         // Tira 404: Not Found
         $command = ViewCommand::display( '404',
                                          new ArrayObject(array('message'=>'El controlador <b>'.$lr['controller'].'</b> no existe')),
                                          new ArrayObject() );
      }
      else // Existe app y controller
      {
         // Aca deberia chekear si la clase $lr['controller'] . "Controller" tiene le metodo $lr['action'] . "Action".
         // Esto igual salta en el executer cuando intenta llamar al metodo, y salta si no existe.
           
         // Logger::struct($lr, "LOGICAL ROUTE 2");
          
         // FIXME: esto es una regla re ruteo.
         // TODO: Si accede a la app sin poner el controller, se intenta buscar un controller con el mismo nombre de la app.
         //       Si no existe, se redirige al core controller como se hace aqui.
          
         // Verificacion de controller filters (v0.1.6.3)
         $controllerFiltersPath = 'apps/'.$lr['app'].'/AppControllerFilters.php'; // Nombre y ubicacion por defecto.
         $controllerFiltersInstance = NULL;
         if ( file_exists($controllerFiltersPath) ) // TODO: no ir al filesystem en cada request, una vez que se pone en prod se debe saber que el archivo existe o no.
         {
            // FIXME: con la carga bajo demanda de PHP esto se haria automaticamente!
            include_once( $controllerFiltersPath ); // FIXME: no usa YuppLoader (nombre de archivo no sigue estandares!).
            $controllerFiltersInstance = new AppControllerFilters(); // Esta clase esta definida en el archivo incluido (es una convension de Yupp).
         }
          
         //Logger::struct( $router->getParams(), "ROUTER PARAMS " .__FILE__.' '.__LINE__ );
         //Logger::struct( $_POST, "POST " .__FILE__.' '.__LINE__ );
         //Logger::struct( $_GET, "GET " .__FILE__.' '.__LINE__ );
          
         $executer = new Executer( $router->getParams() );
         $command = $executer->execute( $controllerFiltersInstance ); // $controllerFiltersInstance puede ser null!
      }
      
      // /ROUTING
      // ====================================================

      // Aun mejor, si devuelvo un array, lo tomo como modelo y tomo la accion y controller para encontrar el view, si el view existe o no, lo trato luego con paginas logicas o views escaffoldeados...
      // Si no devuelve nada, hago lo mismo, y tomo como modelo un array vacio, lo que podria hacer, es si el controller tiene atributos, es usar esos atributos (los valores) como modelo (y los nombres los uso como key en el model).
      // View/Redirect
      // TODO:....
      // Si no vienen las cosas seteadas puedo adivinar por ejemplo que view mostrar en funcion de la accion y contorller, como en grails.
      // TODO: Verificar si no es null, si tiene todos los atributos necesarios para hacer o que dice el comando, etc.
      // FIXME: SI EL COMANDO ES NULL QUIERO HACER ACCIONES POR DEFECTO! como mostrar la view correspondiente al controller, y la action ejecutadas.
      if ($command === NULL || empty($command))
      {
         // O le falta el command o es que la accion es de pedir un recurso estatico el que se devuelve como stream.
         // Error 500: Internal Server Error
         $command = ViewCommand::display( '500',
                                          new ArrayObject(array('message'=>'Hubo un error al crear el comando')),
                                          new ArrayObject() );
      }
      
      // ==============
      // TEST: ver si guarda el estado en la sesion
      //$test = CurrentFlows::getInstance()->getFlow( 'createUser' );
      //Logger::show( "Flow en sesion antes de hacer render: " . print_r($test->getCurrentState(), true) . ", " . __FILE__ . " " . __LINE__ );
      // ================

      // Siempre llega algun comando
      if ( $command->isDisplayCommand() )
      {
         // Aqui llegan tambien los errores ej 500 o 404 para mostrar una vista linda.        

         // FIXME: mostrar o no el tiempo de procesamiento deberia ser configurable.
         //$tiempo_final = microtime(true);
         //$tiempo_proc = $tiempo_final - $tiempo_inicio;
         $timer_process->stop();
         $tiempo_proc = $timer_process->getElapsedTime();
         
         //$tiempo_inicio = microtime(true);
         $timer_render = new Timer();
         $timer_render->start();
  
         // FIXME: en router esta toda la info, porque pasar todo?
         self::render( $lr, $command, $ctx, $router );
        
         //$tiempo_final = microtime(true);
         //$tiempo_render = $tiempo_final - $tiempo_inicio;
         $timer_render->stop();
         $tiempo_render = $timer_render->getElapsedTime();
  
         // TODO: configurar si se quiere o no ver el tiempo de proceso.
         //echo "<br/><br/>Tiempo de proceso: " . $tiempo_proc . " s<br/>";
         //echo "Tiempo de render: " . $tiempo_render . " s<br/>";
  
         return;
      }
      else if ( $command->isStringDisplayCommand() ) // mostrar string
      {
         echo $command->getString();
         return;
      }
      else if ( $command->isDisplayTemplateCommand() ) // mostrar template
      {
         $params = array();
         // TODO: poder pasarle path al helper, asi puedo poner el template en cualquier lado.
         $params['name'] = $command->viewName(); // Nombre del template
         $params['args'] = $command->params();
         Helpers::template($params);
         return;
      }
      else // Es redirect porque no hay otro tipo...
      {
        // TODO: me gustaria poner todo esto en una clase "Redirect".
        
        // echo "DICE QUE NO ES DISPLAY!!!!";
        // La idea es que cmo es excecute, redirija a unc compo/controller/action/params que diga el command.
        // Entonces es reentrante a este modulo, el problema es que no tengo el
        // request hecho de forma que pueda llamarlo de afuera, deberia hacerlo aparte
        // llamar a ese para la primer entrada y las posibles redirecciones que se puedan hacer.
        //
        // -> excecuteControllerAction

        // Que hago con el command que tira este? tengo que revisar las llamadas recursivas...
        //$command = self::excecuteControllerAction( $app->app(), $app->controller(), $app->action(), $urlproc->params() )

        // FIXME: no hace nada con el model, deberia pasar lo que puede como params de GET.
        // TODO: habria que ver como hacer un request por POST asi puedo mandar info sin que se vea en el request.
        $model = Model::getInstance();
        $model->addFlash( $command->flash() );

        // Uso el helper para armar la url. Obs> hay funciones estandar de php que arman urls, como 
        // - http://www.php.net/manual/es/function.http-build-url.php
        // - http://www.php.net/manual/es/function.http-build-str.php //
        //
        $url_params = array();
        $url_params['app']        = $command->app();
        $url_params['controller'] = $command->controller();
        $url_params['action']     = $command->action();
        $url_params['params']     = $command->params(); // T#63 solo pasar los params del modelo no los del request.
        
        // Agrega params a la url (fix a perdida del flash en redirect)
        foreach ($command->flash() as $key => $value)
        {
           // FIXME: si en flash se ponen arrays y se hace redirect, urlencode va a fallar porque espera un string...
           $url_params['flash_'.$key] = urlencode( $value ); // Por ejemplo flash.message='un mensaje', url encode por si tiene simbolos.
        }
        
        //print_r( $url_params );
        
        $url = Helpers::url( $url_params );

        // http://www.php.net/manual/es/function.http-redirect.php
        // retorna false si no puede, exit si si puede.
        //http_redirect( $url ); // [, array $params  [, bool $session = FALSE  [, int $status  ]]]] )

        if ( !headers_sent() )
        {
           // No funciona si hay algun output antes, como el log prendido.
           // http://www.php.net/header
           //
           header( 'Location: http://'. $_SERVER['HTTP_HOST'] . $url ) ;
           exit;
        }
        else
        {
           // TODO: esto deberia ser un template de redireccion automatica fallida.
           // TODO: los mensajes deberian ser i18n.
           $url = 'http://'. $_SERVER['HTTP_HOST'] . $url;
           echo "<html>" .
           "<head></head><body>".
           "Ya se han enviado los headers por lo que no se puede redirigir de forma automatica.<br/>".
           "Intenta redirigir a: <a href=\"$url\">$url</a>".
           "</body></html>";
        }

        // TODO: Puede redirigir a una pagina logica (como en el CMS) o a una pagina de scaffolding 
        // (no existe fisicamente pero se genera mediante un template y muestra la info que se le
        // pasa de forma estandar, considerando si es un list, show, create o edit).
        //return; // TODO
      }

      // NO DEBERIA LLEGAR ACA, DEBE HACERSE UN RENDER O UN REDIRECT ANTES...
   }
   
   private static function render( $logic_route, $command, $context, $router )
   {
      // Configuro el command para la view...

      // Si la pagina es fisica
      $pagePath = 'apps/'.$logic_route['app'].'/views/'.$logic_route['controller'].'/'.$command->viewName().'.view.php';
      
      //echo $pagePath . '<br/>';
      
      //Logger::struct($logic_route, 'render logic route');
      //Logger::struct($command, 'render command');
      
      //$pagePath = realpath('./'.$pagePath); // Resuelve .. y .
      // FIXME: en linux puede tener problemas resolviendo . y .. en la path, necesitaria resolverla a la canonica
      //        realpath se desactiva en algunos hostings por temas de seguridad, habria que implementar una alternativa
      //        http://php.net/manual/en/function.realpath.php
      if (!file_exists($pagePath) ) // Si la pagina NO es fisica
      {
         // Intento resolver path con .. en linux
         $pagePath = preg_replace('/\w+\/\.\.\//', '', $pagePath); // Saca .. pero no saca . y deberia
      }
      
      //echo $pagePath . '<br/>';
      //apps/cms2/views/page/../cms/displayPageRO.view.php
      //apps/cms2/views/cms/displayPageRO.view.php
      
      // Si la ruta referenciada no existe, intento mostrar la vista de scafolding correspondiente
      // a la accion, pero las acciones con vistas dinamicas son solo para acciones: 'show','list','edit','create'.
      if (!file_exists($pagePath)) // Si la pagina NO es fisica
      {
         //Logger::getInstance()->log("no existe pagePath $pagePath " . __FILE__);
         //Logger::getInstance()->log("view name es " . $command->viewName());

         // Si puedo mostrar la vista dinamica:
         if ( in_array($command->viewName(),
                       array('show','list','edit','create','index','appControllers','dbStatus')) )
         {
            $pagePath = 'core/mvc/view/scaffoldedViews/'.$command->viewName().'.view.php'; // No puede no existir, es parte del framework!
         }
         else if ( is_numeric( $command->viewName() )) // Es un error como 404 o 500
         {
            // FIXME: verificar que existe, porque no se implementaron todos los errores...
            $pagePath = 'core/mvc/view/error/'.$command->viewName().'.view.php'; // No puede no existir, es parte del framework!
            
            // FIXME: poner un error general por si no esta en la lista
            $codes = array(403=>'Forbidden',
                           404=>'Not Found',
                           500=>'Internal Server Error');
            
            header("HTTP/1.0 ".$command->viewName()." ".$codes[$command->viewName()]);
         }
         else
         {
            // Tira 404: Not Found

            // Sobreescribo el command
            $command = ViewCommand::display( '404',
                                              new ArrayObject(array('message'=>'La vista <b>'.$pagePath.'</b> no existe')),
                                              new ArrayObject() );
            $pagePath = 'core/mvc/view/error/404.view.php';
            
            header("HTTP/1.0 404 Not Found");
         }
         
         // FIXME: con esto de arriba no es necesario tener mas el "mode".
      }
      
      $command->setPagePath( $pagePath ); // FIXME: No se usa para nada el pagePath en el command.

      // Model va a ser accedida desde las vistas.
      $model = Model::getInstance();
      $model->setModel( $command->params() ); // $command->params() es el modelo devuelto por la accion del controller.
      $model->addFlash( $command->flash() );
      
      // Solucion a agregar flash cuando se hace redirect
      $model->addFlash( $router->getFlashParams() );
      
      /// ACTUALIZAR CONTEXTO ///
      $context->setModel ( $model );
      //$context->update();
      /// ACTUALIZAR CONTEXTO ///

      $layoutManager = LayoutManager::getInstance();
      $layoutManager->renderWithLayout( $pagePath );
      
   } // render
}
?>