<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */

class AppControllerFilters implements IAppControllerFilters {
   
   public static function getBeforeFilters()
   {
      return array('CoreSecurityFilter');
   }
   
   public static function getAfterFilters()
   {
      return array();
   }
}

class CoreSecurityFilter extends YuppController implements IControllerBeforeFilter {
   
   // Pueden ser: un array (controller), un nombre de un 'app.controller' o una action, "app.*" que es "para todos".
   private $controllerActions = "*"; // Lista de controllers a los que se aplica este filter.
   private $exceptControllerActions = array(
                                        //"core"=>array("login", "registerUser", "logout", "sendPassword")
                                      );
   
   public function getAllFilters()
   {
      return $this->controllerActions;
   }

   public function getAllExceptions()
   {
      return $this->exceptControllerActions;
   }
   
   /**
    * Debe retornar true si pasa o un ViewCommand si no pasa, o sea redireccionar o ejecutar una accion de un cotroller o hacer render de un string...
    * FIXME: $app ya no es necesario xq el filtro es por app.
    */
   public function apply($app, $controller, $action)
   {
      $mode = YuppConfig::getInstance()->getCurrentMode();
      
      // En prod no deberia poder ejecutar las acciones de gestion de CoreController
      if ($mode == YuppConfig::MODE_PROD)
      {
         return ViewCommand::display( '403',
                                      new ArrayObject(array('message'=>'No puede ejecutar tareas de gesti&oacute;n en modo PROD')),
                                      new ArrayObject() );
      }
    
      // En otro caso, lo dejo pasar tranquilo
      return true;
   }
}

?>