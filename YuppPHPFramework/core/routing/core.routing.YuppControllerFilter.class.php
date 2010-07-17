<?php

// FIXME: cambiar "*" por una constante ALL_CONTROLLERS, y si es para acciones ALL_ACTIONS (p.e. con un '+')

/**
 * 
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
class YuppControllerFilter {

    /**
     * Arrays de filtros, en before_filters son clases que implementan IControllerBeforeFilter,
     * y en after_filters clases que implementan IControllerAfterFilter.
     */ 
    private $before_filters;
    private $after_filters;

    function __construct( $before_filters, $after_filters )
    {
        $this->before_filters = $before_filters;
        $this->after_filters  = $after_filters;
    }
    
    /**
     * Retorna true si pasa los filters y un ViewCommand si no.
     */
    public function before_filter($component, $controller, $action, ArrayObject $params)
    {
       //print_r($this->before_filters);
    	 foreach ( $this->before_filters as $filterClass )
       {
          // FIXME: no se porque tenia component si el contructor del controller no tiene...
       	 //$filterInstance = new $filterClass($component, $controller, $action, $params); // Extiende controller por eso necesita los parametros en el constructor
          //$filterInstance = new $filterClass($controller, $action, $params); // Extiende controller por eso necesita los parametros en el constructor
          $filterInstance = new $filterClass($params); 
          if ( $this->applies($filterInstance, $component, $controller, $action) )
          {
//             echo "FILTRO APLICA $filterClass, $component, $controller, $action<br/>";
          	 $res = $filterInstance->apply( $component, $controller, $action );
             if ( $res !== true )
             {
//                print_r( $res );
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
//          else echo "FILTRO NO APLICA $filterClass, $component, $controller, $action<br/>";
       }
       
       
       // Si llega aca es que todos los filtros pasaron.
       return true;
    }
    
    // TODO: a after le podria pasar el ViewCommand que genero la accion ejecutada, el modelo, los params, etc.
    public function after_filter($component, $controller, $action, ArrayObject $params, ViewCommand $command)
    {
       foreach ( $this->after_filters as $filterClass )
       {
          // FIXME: no se porque tenia component si el contructor del controller no tiene...
          // Extiende controller por eso necesita los parametros en el constructor.
          //$filterInstance = new $filterClass($component, $controller, $action, $params);
          //$filterInstance = new $filterClass($controller, $action, $params);
          $filterInstance = new $filterClass($params);
          if ( $this->applies($filterInstance, $component, $controller, $action) )
          {
             $res = $filterInstance->apply( $component, $controller, $action, $command );
             
             if ( $res !== true )
             {
                if ( $res === NULL || get_class($res) !== 'ViewCommand' ) throw new Exception("El filtro $filterClass no está retornando un tipo válido en apply.");
               
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
    private function applies( $filterInstance, $component, $controller, $action )
    {
       if (!($filterInstance instanceof IControllerBeforeFilter) && !($filterInstance instanceof IControllerAfterFilter))
       {
       	 throw new Exception("filterInstance debe ser IControllerBeforeFilter o IControllerAfterFilter y es " . get_class($filterInstance));
       }
      
       $filters    = $filterInstance->getAllFilters();
       $exceptions = $filterInstance->getAllExceptions();
       
       //echo "applies: " . get_class($filterInstance) . " $component $controller $action <br/>";
       //echo "CONTROLLERS: $controllers<br/>";
       //echo "ACTIONS : $actions<br/>";
       
       // TODO: verificar que el controllers y el actions tienen alguno de los formatos definidos, si no, tirar except.
       
       if ( $filters === "*")
       {
         // chekeo solo excepciones
         if (array_key_exists($controller, $exceptions)) // si hay una excepcion para el controller
         {
            if ($exceptions[$controller] === "*") return false; // Excepcion para todas las acciones
            else if ( in_array( $action, $exceptions[$controller] ) ) return false; // Excepcion para una accion
         }
         return true; // El filtro aplica.
       }
       
       if ( is_array($filters) && array_key_exists($controller, $filters) ) // si filters es un array y tiene un filtro para el controller
       {
         if ($filters[$controller] === "*") // si es para todas las acciones del controller
         {
            // solo verifico excepciones
            if (array_key_exists($controller, $exceptions)) // si hay una excepcion para el controller
            {
               // NO TIENE SENTIDO QUE PONGA UN FILTRO PARA TODAS LAS ACCIONES Y UNA EXCEPCION PARA TODAS LAS ACCIONES. TODO: DEBERIA TIRAR UN ERROR S ISE HICIERA ESTO.
               if ( in_array( $action, $exceptions[$controller] ) ) return false; // Excepcion para una accion
            }
            return true; // Aplica el filtro.
         }
         else if (is_array($filters) && in_array($action, $filters[$controller]) ) // TODO: Si llega aca y is_array($filters) da false, hay que tirar un error
         {
            return true; // No tiene sentido poner un filtro para una accion determinada y luego poner excepciones para esa accion o para todas las acciones del controller TODO: si pasa esto deberia tirar un warning o error.
         }
         return false; // si es un array y no aplicaron los criterios de busqueda, no aplica el filtro.
       }
       
       // Si llega aca el chekeo de controller no dio true, por otro lado  puede ser error de tipos, para eso deberia dividir el if del controller en 2
       return false;
       
       // Si llega aca hay algo mal con la definicion de controllers...
       //throw new Exception("Valor dado para controllers es incorrecto para el filtro " . get_class($filterInstance) . ", tiene valor '$controllers'");
    }
} // YuppControllerFilter

interface IComponentControllerFilters {
   
   /**
    * Devuelve un array con todos los filtros configurados en el ComponentControllerFilters del modulo.
    */
   public static function getBeforeFilters();
   public static function getAfterFilters();
}

// Se usa para chekear el tipo de los filters
interface IControllerBeforeFilter {
	
   // Pueden ser: un array, un nombre de un controller o una action, "*" que es "para todos".
   public function getAllFilters();
   public function getAllExceptions();
   
   /**
    * Debe retornar true si pasa o un ViewCommand si no pasa, o sea redireccionar o ejecutar una accion de un cotroller o hacer render de un string...
    */
   public function apply($component, $controller, $action);
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
   public function apply($component, $controller, $action, ViewCommand $command);
}

/*
// FIXME: las acciones deben ser de los controllers, no son sueltas.
// FIXME: deberia estar declarada en otro archivo
// Extiende controller para tener render y redirect!
class BlogSecurityFilter extends YuppController implements IControllerBeforeFilter {
	
   // Pueden ser: un array (component=>controller), un nombre de un 'component.controller' o una action, "*" que es "para todos".
   private $controllers = array( "blog" => array("entradaBlog","comentario") ); // Lista de controllers a los que se aplica este filter.
   private $actions = "*"; // Lista de acciones a los que se aplica el filter
   
   public function getControllersFilter() { return $this->controllers; }
   public function getActionsFilter()     { return $this->actions; }
   
   / **
    * Debe retornar true si pasa o un ViewCommand si no pasa, o sea redireccionar o ejecutar una accion de un cotroller o hacer render de un string...
    * /
   public function apply($component, $controller, $action)
   {
   	// CUSTOM ACTION!
      
      $u = YuppSession::get("user"); // Lo pone en session en el login.
      
      if ( $u !== NULL ) return true;
      
      return $this->redirect( array("component"=>"blog", "controller" => "usuario", "action" => "login") );
   }
}
*/

?>