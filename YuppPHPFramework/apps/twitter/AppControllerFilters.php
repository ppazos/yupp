<?php

class AppControllerFilters implements IAppControllerFilters {
   
   public static function getBeforeFilters()
   {
      return array('TwitterSecurityFilter');
   }
   
   public static function getAfterFilters()
   {
      return array();
   }
}

class TwitterSecurityFilter extends YuppController implements IControllerBeforeFilter {
   
   // Pueden ser: un array (controller), un nombre de un 'app.controller' o una action, "app.*" que es "para todos".
   private $controllerActions = '*'; // Lista de controllers a los que se aplica este filter.
   private $exceptControllerActions = array(
                                        'user'=>array('index', 'login', 'register', 'logout') // open actions for users
                                      );
   
   public function getAllFilters()
   {
      return $this->controllerActions;
   }

   public function getAllExceptions()
   {
      return $this->exceptControllerActions;
   }
   
   public function apply($app, $controller, $action)
   {
      YuppLoader::load('twitter.model', 'TUser');
      
      $user = TUser::getLogged();
      if ($user == NULL)
      {         
         $this->flash['message'] = "Ups, you can't do that, please login...";
         return $this->redirect( array('app'        => 'twitter',
                                       'controller' => 'user',
                                       'action'     => 'login') );
         
         
      }

      return true;
   }
}

?>