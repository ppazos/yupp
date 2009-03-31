<?php

class YuppController {

    protected $params; // ultimos params ya procesados.

    protected $flash = array(); // Sirve para que el usuario ponga elementos que se van a poder acceder desde el view, de forma sencilla (es parecido a un model pero se pasa de forma distinta, ver el CoreController).

    protected $controllerName;
    protected $actionName;

    /**
     * Como el nombre del flow ocupa el lugar de la accion, 
     * el RequestManager (en realidad router.Executer) debe 
     * preguntar si la accion es el nombre de un flow o una accion comun.
     */
    public function flowExists( $actionName )
    {
       return method_exists($this, $actionName . "Flow"); // Se fija si el metodo inicializador del flow existe en el controller.
    }
    
    public function flowLoaded( $actionName )
    {
       return ( $this->getFlow( $actionName ) !== NULL );
    }
    
    public function loadFlow( $flowName )
    {
    	 $flow = $this->{$flowName . "Flow"}();
       CurrentFlows::getInstance()->addFlow( &$flow );
    }
    
    public function &getFlow( $flowName )
    {
       return CurrentFlows::getInstance()->getFlow( $flowName );
    }


    // TODO: Agregar IndexAction que haga un render de una pagina por defecto para el controller.
    

    function __construct($controllerName, $actionName, $params)
    {
       $this->controllerName = $controllerName;
       $this->actionName     = $actionName;
       $this->params         = $params;
    }

    public function __call( $method, $args )
    {
       // Es una accion?
       if (method_exists($this, $method . "Action"))
       {
          return $this->{$method . "Action"}( $args );
       }

       throw new Exception("La accion <b>" . $method . "</b> no existe.");
    }

    public function addToFlash( $key, $value )
    {
    	 $this->flash[$key] = $value;
    }

    public function getFlash($key = NULL)
    {
       if ($key)
       {
          // TODO: verificar key existe...
    	    return $this->flash[$key];
       }
       else
       {
       	 return $this->flash;
       }
    }

    public function renderString( $string ) // FIXME: puedo crearlo sin pasarle los params, xq es un atributo mio.
    {
       return ViewCommand::display_string( $string );
    }

   /**
    * @param String view (comentario/list)
    * @param array params
    */
    public function render( $view, &$params ) // FIXME: puedo crearlo sin pasarle los params, xq es un atributo mio.
    {
    	 return ViewCommand::display( $view, $params, $this->flash );
    }
    
    /**
     * redirect( $params )
     * Redirige el flujo de ejecucion de una accion de un controller a una accion del mismo o de otro controller.
     * 
     * @param $params mapa de parametros en la forma nombre=>valor. Un elemento especial es "params", que es a su vez un mapa de parametros para el request que se lanza.
     */
    public function redirect( $params ) // FIXME: puedo crearlo sin pasarle los params['params'], xq es un atributo mio.
    {
       $ctx = YuppContext::getInstance();

       if ( array_key_exists('component', $params) ) // Si no me lo pasan, tengo que poner el actual.
           $component  = $params['component'];
       else
           $component  = $ctx->getComponent();
           
       if ( array_key_exists('controller', $params) ) // Si no me lo pasan, tengo que poner el actual.
           $controller = $params['controller'];
       else
           $controller = $ctx->getController();
        
       // FIXME: si no se le pasa action se ejecuta la accion index?? deberia tirar una excepcion si no me pasan la accion??
       $action = $params['action'];
       
       if ( !isset($params['params']) ) $params['params'] = array();
       
       return ViewCommand::execute( $component, $controller, $action, $params['params'], $this->flash );
    }
    
   /**
    * Si un controlador no tiene la accion show definida, se ejecuta esta, 
    * que va a la vista dinamica de show por scaffolding.
    */
   public function showAction()
   {
      $context = YuppContext::getInstance();
      
      $id = $this->params['id'];
      $clazz = String::firstToUpper( $context->getController() );

      // La clase debe estar cargada...
      eval ('$obj' . " = $clazz::get( $id );");

      $this->params['object'] = $obj;
      $this->params['mode'] = "show"; // Para saber que pagina es.

      return $this->render(NULL, & $this->params); // Id NULL para paginas de scaffolding
   }
   
   /**
    * Si un controlador no tiene la accion show definida, se ejecuta esta, 
    * que va a la vista dinamica de show por scaffolding.
    */
   public function createAction()
   {
      $context = YuppContext::getInstance();
      $clazz = String::firstToUpper( $context->getController() );
      $obj = new $clazz (); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

      // View create, que es como edit pero la accion de salvar vuelve aqui.

      if ($this->params['doit']) // create
      {
         $obj->setProperties($this->params);
         if (!$obj->save()) // Con validacion de datos!
         {
            // create
            $this->params['object'] = $obj;
            $this->params['mode'] = "create"; // Para saber que pagina es.
            return $this->render(NULL, $this->params);
         }

         // show
         $this->params['object'] = $obj;
         $this->params['mode'] = "show"; // Para saber que pagina es.
         return $this->render(NULL, $this->params);
      }

      // create
      $this->params['object'] = $obj;
      $this->params['mode'] = "create"; // Para saber que pagina es.
      return $this->render(NULL, $this->params);
   }
}

?>