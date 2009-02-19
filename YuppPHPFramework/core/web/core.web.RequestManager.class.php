<?php

YuppLoader::load("core.layout", LayoutManager);

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
         
         
         //Logger::struct( $filter->getPath() );
//         Logger::struct($lr, "LOGICAL ROUTE 1");
         
         // Verifica salida del router y setea valores que no vienen seteados.
         // TODO: OJO, luego debe pasar el verificador de si el controller y action existen, y si no, ejecutar contra core.
         if ( $lr['component']  === NULL ) $lr['component'] = "core";
         if ( $lr['controller'] === NULL || $lr['controller'] === "" )
         {
            $lr['component']  = "core"; // no puede ser controller core sin ser el componente core.
            $lr['controller'] = "core";
         }
         
         // Prefiero _action_laAccion a la accion que viene en la URL.
         $actionParam = $filter->getActionParam(); // Por si la accion viene codificada en una key de un param como '_action_laAccion', por ejemplo: esto pasa en un submit de un YuppForm.
         if ($actionParam === NULL || $actionParam === "") // Solo si no hay actionParam, me fijo si viene en la url.
         {
            if ( $lr['action'] === NULL || $lr['action'] === "" )
            {
               $lr['action'] = "index";
            }
            else
            {
               //echo "<h1>C</h1>";
            	//echo "<h1>ACTION 1: ". $lr['action'] ."</h1>";
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
         $componentPath = "components/".$lr['component'];
         
         $controllerClassName = String::firstToLower($lr['controller']) . "Controller";
         $controllerFileName  = "components.".$lr['component'].".controllers.".$controllerClassName.".class.php";
         $controllerPath      = "components/".$lr['component']."/controllers/".$controllerFileName;
         if ( !file_exists($componentPath) )
         {
            throw new Exception("ERROR: routing.componentDoesntExists value: " . $lr['component']);
         }
         else if (!file_exists($controllerPath))
         {
        	   throw new Exception("ERROR: routing.controllerDoesntExists value: " . $lr['controller']);
         }
         // Aca deberia chekear si la clase $lr['controller'] . "Controller" tiene le metodo $lr['action'] . "Action".
         // Esto igual salta en el executer cuando intenta llamar al metodo, y salta si no existe.

//         Logger::struct($lr, "LOGICAL ROUTE 2");
         
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
        
        $executer = new Executer( $filter->getParams() );
        $command = $executer->execute();

        
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
              // Configuro el command para la view...
              // OJO DEBERIA PODER MOSTRAR PAGINAS DE CUALQUIER COMPONENTE!!! LOS TIPOS NO VAN A PONER SUS PAGINAS EN /CORE...
              // Si la pagina es fisica
              $pagePath = "components/".$lr['component']."/views/".$command->viewName().".view.php";
              //$pagePath = "components/".$lr['component']."/views/".$lr['controller']."/".$command->viewName().".view.php";
              // components/blog/views/usuario//usuario/login.view.php

              
              //Logger::show( "LogicalRouteController: " . $lr['controller'], "h1" );
              //Logger::show( "CommandViewName: " . $command->viewName(), "h1" );
              
              if ( !file_exists($pagePath) ) // Si la pagina NO es fisica
              {
                 // Si tiene Id -> es logica y se tiene que armar con metadata de la base
                 // Si no tiene Id, le tiro con pagina de scaffolding.
                 if ($command->viewName())
                 {
                 	  // Pagina dinamica
                    throw new Exception("La vista co path: '$pagePath' no existe, y la pagina dinamica todavia no esta soportada. VERIFIQUE EN EL CONTROLLER QUE LA VISTA QUE QUIERE MOSTRAR EXISTE REALMENTE. " . __FILE__ . " " . __LINE__);
                 }
                 else
                 {
                    // FIXME: funciona solo si el mode es: list, edit, create, show (en realidad deberia ser el nombre de la accion no "mode").
                 	  // Scaffonding dinamico
                    $pagePath = "core/mvc/view/scaffoldedViews/".$command->param("mode").".view.php";
                    
                    if ( !file_exists($pagePath) ) // Si la pagina NO es fisica
                    {
                        throw new Exception("La vista no existe y se intento mostrar una vista dinamica pero tampoco existe: $pagePath " . __FILE__ . " " . __LINE__);
                    }
                 }
              }
              

              $command->setPagePath( $pagePath );

              $model = Model::getInstance();
              $model->setModel( $command->params() ); // $command->params() es el modelo devuelto por la accion del controller.

              // FLASH // Por ahora meto el flash en el model, luego podria ir aparte por prolijidad nomas...
              //$model->setFlash( $controllerInstance->getFlash() ); // PROBLEMA: Si hay redirect, me sobreescribe el flash con vacio, y yo quiero mostrar el flash de la primer accion, por ejemplo en el delete pongo un flahs y luego muestra list y no me muestra en mensaje que puse en delete.
              //$model->addFlash( $controllerInstance->getFlash() );
              
              
//        echo "AAA"; // ERROR, se pierde el flash.
//        $command->show();
//        echo "AAA";

              
              $model->addFlash( $command->flash() );
              
              
              /// ACTUALIZAR CONTEXTO ///
              $ctx->setModel ( &$model );
              $ctx->update();
              /// ACTUALIZAR CONTEXTO ///
              
              
              // FIXME: mostrar o no el tiempo de procesamiento deberia ser configurable.
              $tiempo_final = microtime(true);
              $tiempo_proc = $tiempo_final - $tiempo_inicio;


              $tiempo_inicio = microtime(true);

              LayoutManager::renderWithLayout( $pagePath );

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
           else // Es execute porque no hay otro tipo...
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

               // TODO: Puede redirigir a una pagina logica (como en el CMS) o a una pagina de scaffolding (no existe fisicamente pero se genera mediante un template y muestra la info que se le pasa de forma estandar, considerando si es un list, show, create o edit).

               //return; // TODO
           }

        } // si hay command

        // NO DEBERIA LLEGAR ACA, DEBE HACERSE UN RENDER O UN REDIRECT ANTES...
    }
}
?>