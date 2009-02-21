<?php

/**
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
class ControllerFilter {

    private $before_filters = array( "SecurityFilter" ); // FIXME: esto se esta verificando? se verifica para todos los componentes? (se hizo para el blog).
    private $after_filters  = array();

    function __construct()
    {
    }
    
//    public static function registerBeforeFilter( IControllerFilter $filterClass )
//    {
//    	 self::$before_filters[] = $filterClass;
//    }
//    
//    public static function registerAfterFilter( IControllerFilter $filterClass )
//    {
//       self::$after_filters[] = $filterClass;
//    }
    
    /**
     * Retorna true si pasa los filters y un ViewCommand si no.
     */
    public function before_filter($controller, $action, &$params)
    {
    	 foreach ( $this->before_filters as $filterClass )
       {
       	 $filterInstance = new $filterClass($controller, $action, $params); // Extiende controller por eso necesita los parametros en el constructor
          if ( $this->applies($filterInstance, $controller, $action) )
          {
          	 $res = $filterInstance->apply( $controller, $action );
             if ( $res !== true )
             {
                if ( $res === NULL || get_class($res) !== 'ViewCommand' ) throw new Exception("El filtro $filterClass no está retornando un tipo válido en apply.");
               
                // TODO: verificar que no hayan ocurrido errores, por ejemplo que no se retorne ViewCommand, 
                // por ejemplo se podria retornar null por que el usuario se olvido de hacer un retorno valido.
             	 if ( get_class($res) === 'ViewCommand' )
                {
                	 return $res; // Hace redirect o llama derecho a una accion de un controller.
                }
             }
             // Si es true, sigo ejecutando viendo si otro filter falla o tira ViewCommand
          }
       }
       
       // Si llega aca es que todos los filtros pasaron.
       return true;
    }
    
    // TODO: a after le podria pasar el ViewCommand que genero la accion ejecutada, el modelo, los params, etc.
    public function after_filter($controller, $action, &$params, ViewCommand $command)
    {
       foreach ( $this->after_filters as $filterClass )
       {
          // Extiende controller por eso necesita los parametros en el constructor.
          $filterInstance = new $filterClass($controller, $action, $params);
          if ( $this->applies($filterInstance, $controller, $action) )
          {
             $res = $filterInstance->apply( $controller, $action, $command );
             if ( $res !== true )
             {
                // TODO: verificar que no hayan ocurrido errores, por ejemplo que no se retorne ViewCommand, 
                // por ejemplo se podria retornar null por que el usuario se olvido de hacer un retorno valido.
                if ( get_class($res) === 'ViewCommand' )
                {
                   return $res; // Hace redirect o llama derecho a una accion de un controller.
                }
             }
             // Si es true, sigo ejecutando viendo si otro filter falla o tira ViewCommand
          }
       }
       
       // Si llega aca es que todos los filtros pasaron.
       return true;
    }
    
    /**
     * Verifica si corresponde o no aplicar el filtro para el controlador y acciones dados.
     */
    private function applies( $filterInstance, $controller, $action )
    {
       if (!($filterInstance instanceof IControllerBeforeFilter) && !($filterInstance instanceof IControllerAfterFilter))
       {
       	 throw new Exception("filterInstance debe ser IControllerBeforeFilter o IControllerAfterFilter y es " . get_class($filterInstance));
       }
      
       $controllers = $filterInstance->getControllersFilter();
       $actions     = $filterInstance->getActionsFilter();
       
       if ( $controllers === "*")
       {
         if ($actions === "*") return true;
         if (is_array($actions) && in_array($action, $actions)) return true;
         return ($actions === $action); // caso que actions es un string
       }
       
       if ( is_array($controllers) && in_array($controller, $controllers) )
       {
         if ($actions === "*") return true;
         if (is_array($actions) && in_array($action, $actions)) return true;
         return ($actions === $action); // caso que actions es un string
       }
       
       if ( $controllers === $controller ) // Caso en que controllers es un string, el nombre del controller al que se aplica
       {
         if ($actions === "*") return true;
         if (is_array($actions) && in_array($action, $actions)) return true;
         return ($actions === $action); // caso que actions es un string
       }
       
       // Si llega aca el chekeo de controller no dio true, por otro lado  puede ser error de tipos, para eso deberia dividir el if del controller en 2
       return false;
       
       // Si llega aca hay algo mal con la definicion de controllers...
       //throw new Exception("Valor dado para controllers es incorrecto para el filtro " . get_class($filterInstance) . ", tiene valor '$controllers'");
    }
}


// Se usa para chekear el tipo de los filters
interface IControllerBeforeFilter {
	
   // Pueden ser: un array, un nombre de un controller o una action, "*" que es "para todos".
   public function getControllersFilter();
   public function getActionsFilter();
   
   /**
    * Debe retornar true si pasa o un ViewCommand si no pasa, o sea redireccionar o ejecutar una accion de un cotroller o hacer render de un string...
    */
   public function apply($controller, $action);
}

interface IControllerAfterFilter {
   
   // Pueden ser: un array, un nombre de un controller o una action, "*" que es "para todos".
   public function getControllersFilter();
   public function getActionsFilter();
   
   /**
    * Debe retornar true si pasa o un ViewCommand si no pasa, o sea redireccionar o ejecutar 
    * una accion de un cotroller o hacer render de un string...
    * Recibe el ViewCommand que retorna la accion del controller luego de ser ejecutada.
    */
   public function apply($controller, $action, ViewCommand $command);
}

// FIXME: depende del componente blog!
// FIXME: deberia estar declarada en otro archivo
// Extiende controller para tener render y redirect!
class SecurityFilter extends YuppController implements IControllerBeforeFilter {
	
   // Pueden ser: un array, un nombre de un controller o una action, "*" que es "para todos".
   private $controllers = array( "entradaBlog", "comentario" ); // Lista de controllers a los que se aplica este filter.
   private $actions = "*"; // Lista de acciones a los que se aplica el filter
   
   public function getControllersFilter() { return $this->controllers; }
   public function getActionsFilter()     { return $this->actions; }
   
   /**
    * Debe retornar true si pasa o un ViewCommand si no pasa, o sea redireccionar o ejecutar una accion de un cotroller o hacer render de un string...
    */
   public function apply($controller, $action)
   {
   	// CUSTOM ACTION!
      
      $u = YuppSession::get("user"); // Lo pone en session en el login.
      
      if ( $u !== NULL ) return true;
      
      return $this->redirect( array("controller" => "usuario", "action" => "login") );
   }
}

?>