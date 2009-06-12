<?php

YuppLoader::load("core.layout", "LayoutManager");

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
       
      // ======================
      // PARTE DE ROUTING
      // ======================

      // TODO: que el mostrar el tiempo de proceso sea configurable.
      $tiempo_inicio = microtime(true);

      // Establezco la url base, independiente del directorio donde este situado el script.
      // Si la uri es: http://localhost:8081/Persistent/test/usermanager/person/create?name=pepe&age=23&height=180
      // y este script esta en http://localhost:8081/Persistent/test/
      // Url sera: usermanager/person/create?name=pepe&age=23&height=180

      // ====================================================
      // ROUTING 

      $filter = new Filter( $_SERVER['REQUEST_URI'] ); // Toma la url y extrae sus partes para procesamiento posterior.
      $mapping = new Mapping( $filter->getPath() ); // Toma la ruta y extrae el nombre del componente, controller y accion. (no hay chequeos, pueden no existir!)
      $lr = $mapping->getLogicalRoute();
         
      //Logger::struct( $lr, __FILE__ . " " . __LINE__ );
      //Logger::struct( $filter->getPath() );
//      Logger::struct($lr, "LOGICAL ROUTE 1");
         
      // Verifica salida del router y setea valores que no vienen seteados.
      // TODO: OJO, luego debe pasar el verificador de si el controller y action existen, y si no, ejecutar contra core.
      
      if ( $lr['component']  === NULL || $lr['component'] === "" )
      {
         $config = YuppConfig::getInstance();
         $modeDefaultMapping = $config->getModeDefaultMapping();
         $lr['component']  = $modeDefaultMapping['component'];
         $lr['controller'] = $modeDefaultMapping['controller'];
         $lr['action']     = $modeDefaultMapping['action'];
         
         $filter->addCustomParams( $modeDefaultMapping['params'] ); // Agrego los params necesarios a filter que es quien mantiene los parametros de get y post.
      }
      
      if ( $lr['controller'] === NULL || $lr['controller'] === "" ) // Si me pone el componente pero no el controller, entonces no se que hago... (poner core/core es un FIX nomas)
      {
         //throw new Exception("ERROR: Se especifica el componente pero no el controlador, el controlador es obligatorio y es el segundo argumento de la url: componente/controlador/accion/params " . __FILE__ . " " . __LINE__);
         // FIXME: tirar 404
         
         // Si la ruta en la URL llega hasta el componente, se muestran los controladores del componente.
         $filter->addCustomParams( array('component'=>$lr['component']) );
         $lr['component'] = "core";
         $lr['controller'] = "core";
         $lr['action'] = "componentControllers";
      }
         
      // Prefiero el parametro por url "_action_nombreAccion", a la accion que viene en la URL (componente/controlador/accion).
      // Esto es porque los formularios creados con YuppForm generan acciones distintas para botones de 
      // submit distintos y la accion es pasada codificada en un parametros _action_nombreAcction.
      $actionParam = $filter->getActionParam(); // Por si la accion viene codificada en una key de un param como '_action_laAccion', por ejemplo: esto pasa en un submit de un YuppForm.
      if ($actionParam === NULL || $actionParam === "") // Solo si no hay actionParam, me fijo si viene en la url.
      {
         if ( !isset($lr['action']) || $lr['action'] === "" )
         {
            $lr['action'] = "index";
         }
      }
      else
      {
         $lr['action'] = $actionParam;
      }
           
      // *******************************************************************************
      // FIXME: puedo tener componente, controlador y accion, pero pueden ser nombres
      // errados, es decir, que no existen, por ejemplo si en la url le paso /x/y/z.
      // Aqui hay que verificar si existe antes de seguir, y si el componente no existe,
      // o si existe pero el controlador no existe, o si ambos existen, si la accion 
      // en el controlador no existe, deberia devolver un error y mostrarlo lindo (largar una exept).
      // Estaria bueno definir codigos estandar de errores de yupp, para poder tener una
      // lista ed todos los errores que pueden ocurrir.
      // *******************************************************************************
      
      $componentPath       = "components/".$lr['component'];
      $controllerClassName = String::firstToUpper($lr['controller']) . "Controller";
      $controllerFileName  = "components.".$lr['component'].".controllers.".$controllerClassName.".class.php";
      $controllerPath      = "components/".$lr['component']."/controllers/".$controllerFileName;
      
      //print_r( $lr );
      //echo "<hr/>PATH: $controllerPath<br/>";
      
      if ( !file_exists($componentPath) )
      {
         throw new Exception("routing.componentDoesntExists value: ". $lr['component'] ." ". __FILE__ ." ". __LINE__);
         // FIXME: tirar 404
      }
      else if (!file_exists($controllerPath))
      {
        	throw new Exception("routing.controllerDoesntExists value: ". $lr['controller'] ." ". __FILE__ ." ". __LINE__);
         // FIXME: tirar 404
      }
      // Aca deberia chekear si la clase $lr['controller'] . "Controller" tiene le metodo $lr['action'] . "Action".
      // Esto igual salta en el executer cuando intenta llamar al metodo, y salta si no existe.
       
//     Logger::struct($lr, "LOGICAL ROUTE 2");
         
      // /ROUTING
      // ====================================================

      // FIXME: esto es una regla re ruteo.
      // TODO: Si accede al componente sin poner el controller, se intenta buscar un controller con el mismo nombre del componente.
      //       Si no existe, se redirige al core controller como se hace aqui.
      
      /// ACTUALIZAR CONTEXTO ///
      $ctx = YuppContext::getInstance();
      $ctx->setComponent ( $lr['component'] );
      $ctx->setController( $lr['controller'] );
      $ctx->setAction    ( $lr['action'] );
      $ctx->update();
      /// ACTUALIZAR CONTEXTO ///
        
      //Logger::struct( $filter->getParams(), "FILTER->getParams" );
      
      // Verificacion de controller filters (v0.1.6.3)
      $controllerFiltersPath = "components/".$lr['component']."/ComponentControllerFilters.php"; // Nombre y ubicacion por defecto.
//      Logger::show( $controllerFiltersPath, "h1" );
      $controllerFiltersInstance = NULL;
      if ( file_exists($controllerFiltersPath) ) // TODO: no ir al filesystem en cada request, una vez que se pone en prod se debe saber que el archivo existe o no.
      {
//         Logger::show( "el archivo existe", "h1" );
         // FIXME: con la carga bajo demanda de PHP esto se haria automaticamente!
         include_once( $controllerFiltersPath ); // FIXME: no usa YuppLoader (nombre de archivo no sigue estandares!).
         $controllerFiltersInstance = new ComponentControllerFilters(); // Esta clase esta definida en el archivo incluido (es una convension de Yupp).
      }
      
      $executer = new Executer( $filter->getParams() );
      $command = $executer->execute( $controllerFiltersInstance ); // $controllerFiltersInstance puede ser null!


//print_r( $command );
//    echo "SSS"; // OK tiene el flash.
//    $command->show();
//    echo "SSS";

      // ======================
      // /PARTE DE ROUTING
      // ======================

      // Aun mejor, si devuelvo un array, lo tomo como modelo y tomo la accion y controller para encontrar el view, si el view existe o no, lo trato luego con paginas logicas o views escaffoldeados...
      // Si no devuelve nada, hago lo mismo, y tomo como modelo un array vacio, lo que podria hacer, es si el controller tiene atributos, es usar esos atributos (los valores) como modelo (y los nombres los uso como key en el model).
      // View/Redirect
      // TODO:....
      // Si no vienen las cosas seteadas puedo adivinar por ejemplo que view mostrar en funcion de la accion y contorller, como en grails.
      // TODO: Verificar si no es null, si tiene todos los atributos necesarios para hacer o que dice el comando, etc.
      // FIXME: SI EL COMANDO ES NULL QUIERO HACER ACCIONES POR DEFECTO! como mostrar la view correspondiente al controller, y la action ejecutadas.
      if (!$command)
      {
         // O le falta el command o es que la accion es de pedir un recurso estatico el que se devuelve como stream.
         //echo "<h1>RequestManager: COMMAND=NULL TODAVIA NO SOPORTADO!</h1>";
      }
      else
      {
         if ( $command->isDisplayCommand() )
         {
            // FIXME: mostrar o no el tiempo de procesamiento deberia ser configurable.
            $tiempo_final = microtime(true);
            $tiempo_proc = $tiempo_final - $tiempo_inicio;
            $tiempo_inicio = microtime(true);
      
            self::render( $lr, $command, $ctx, $filter );
            
            $tiempo_final = microtime(true);
            $tiempo = $tiempo_final - $tiempo_inicio;
      
            echo "<br/><br/>Tiempo de proceso: " . $tiempo_proc . " s<br/>";
            echo "Tiempo de render: " . $tiempo . " s<br/>";
      
            return;
              
         } // isDisplayCommand
         else if ( $command->isStringDisplayCommand() )
         {
            echo $command->getString();
            return;
              
         } // isStringDisplayCommand
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
            //$command = self::excecuteControllerAction( $component->component(), $component->controller(), $component->action(), $urlproc->params() )

//        echo "BBB"; // ERROR, se pierde el flash.
//        $command->show();
//        echo "BBB";

            // FIXME: no hace nada con el model, deberia pasar lo que puede como params de GET.
            // TODO: habria que ver como hacer un request por POST asi puedo mandar info sin que se vea en el request.
            $model = Model::getInstance();
            $model->addFlash( $command->flash() );

            // Uso el helper para armar la url. Obs> hay funciones estandar de php que arman urls, como 
            // - http://www.php.net/manual/es/function.http-build-url.php
            // - http://www.php.net/manual/es/function.http-build-str.php //
            //
            $url_params = $command->params(); //$urlproc->params(); // T#63 solo pasar los params del modelo no los del request.
            $url_params['component']  = $command->component();
            $url_params['controller'] = $command->controller();
            $url_params['action']     = $command->action();
            
            // Agrega params a la url (fix a perdida del flash en redirect)
            foreach ($command->flash() as $key => $value)
            {
               $url_params['flash_'.$key] = urlencode( $value ); // Por ejemplo flash.message='un mensaje', url encode por si tiene simbolos.
            }
            
            //print_r( $url_params );
            
            $url = Helpers::url( $url_params );
               
            //echo "URL: http://". $_SERVER['HTTP_HOST'] . $url ."<hr/>"; // OK!
            //print_r($_SERVER);
            //$_SERVER['REQUEST_URI'] = $url; // reescribo la url en el request y hago un request reentrante.
            //self::doRequest(); // recursiva

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
               "<head>".
               "</head>".
               "<body>".
               "Ya se han enviado los headers por lo que no se puede redirigir de forma automatica.<br/>".
               "Intenta redirigir a: <a href=\"$url\">$url</a>".
               "</body>".
               "</html>";
            }

            // TODO: Puede redirigir a una pagina logica (como en el CMS) o a una pagina de scaffolding 
            // (no existe fisicamente pero se genera mediante un template y muestra la info que se le
            // pasa de forma estandar, considerando si es un list, show, create o edit).
            //return; // TODO
         }
      } // si hay command
      // NO DEBERIA LLEGAR ACA, DEBE HACERSE UN RENDER O UN REDIRECT ANTES...
   }
   
   private static function render( $logic_route, $command, $context, $filter )
   {
      // Configuro el command para la view...
      // OJO DEBERIA PODER MOSTRAR PAGINAS DE CUALQUIER COMPONENTE!!! LOS TIPOS NO VAN A PONER SUS PAGINAS EN /CORE...
      // Si la pagina es fisica
      //$pagePath = "components/".$lr['component']."/views/".$command->viewName().".view.php"; // ViewName incluye el controller.
      $pagePath = "components/".$logic_route['component']."/views/".$logic_route['controller']."/".$command->viewName().".view.php";
      
      // Si la ruta referenciada no existe, intento mostrar la vista de scafolding correspondiente
      // a la accion, pero las acciones con vistas dinamicas son solo para acciones: "show","list","edit","create".
      if ( !file_exists($pagePath) ) // Si la pagina NO es fisica
      {
         // Si puedo mostrar la vista dinamica:
         if ( in_array($command->viewName(), array("show","list","edit","create","index","componentControllers")) )
         {
            $pagePath = "core/mvc/view/scaffoldedViews/".$command->viewName().".view.php"; // No puede no existir, es parte del framework!
         }
         else
         {
            throw new Exception("La vista con path: '$pagePath' no existe. VERIFIQUE EN EL CONTROLLER QUE LA VISTA QUE QUIERE MOSTRAR EXISTE. " . __FILE__ . " " . __LINE__);
         }
         
         
         // FIXME: con esto de arriba no es necesario tener mas el "mode".
         
         /*
         // Si tiene Id -> es logica y se tiene que armar con metadata de la base
         // Si no tiene Id, le tiro con pagina de scaffolding.
         if ($command->viewName())
         {
            // Pagina dinamica
            throw new Exception("La vista con path: '$pagePath' no existe, y la pagina dinamica todavia no esta soportada. VERIFIQUE EN EL CONTROLLER QUE LA VISTA QUE QUIERE MOSTRAR EXISTE REALMENTE. " . __FILE__ . " " . __LINE__);
         }
         else
         {
            // FIXME: funciona solo si el mode es: list, edit, create, show (en realidad deberia ser el nombre de la accion no "mode").
            // Scaffonding dinamico
            $pagePath = "core/mvc/view/scaffoldedViews/".$command->param("mode").".view.php";
              
            if ( !file_exists($pagePath) ) // Si la pagina NO es fisica
            {
               throw new Exception("La vista no existe y se intento mostrar una vista dinamica pero tampoco existe: $pagePath " . __FILE__ . " " . __LINE__);
               // FIXME: tirar 404
            }
         }
         */
      }
      
      $command->setPagePath( $pagePath ); // FIXME: No se usa para nada el pagePath en el command.

      // Model va a ser accedida desde las vistas.
      $model = Model::getInstance();
      $model->setModel( $command->params() ); // $command->params() es el modelo devuelto por la accion del controller.
      $model->addFlash( $command->flash() );
      $model->addFlash( $filter->getFlashParams() ); // Solucion a agregar flash cuando se hace redirect.
        
      /// ACTUALIZAR CONTEXTO ///
      $context->setModel ( &$model );
      $context->update();
      /// ACTUALIZAR CONTEXTO ///
      
      //Logger::struct( $context, __FILE__ . " " . __LINE__ );

      LayoutManager::renderWithLayout( $pagePath );
      
   } // render
   
}
?>