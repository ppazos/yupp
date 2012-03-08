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
       CurrentFlows::getInstance()->addFlow( $flow );
    }
    
    public function &getFlow( $flowName )
    {
       return CurrentFlows::getInstance()->getFlow( $flowName );
    }

    // TODO: Agregar IndexAction que haga un render de una pagina por defecto para el controller.
    
    // Ahora controller y action los obtiene de Context, no es necesario pasarselos como parametro.
    //function __construct($controllerName, $actionName, ArrayObject $params)
    function __construct(ArrayObject $params)
    {
       $ctx = YuppContext::getInstance();
       //$component  = $ctx->getComponent();
       $this->controllerName = $ctx->getController();
       $this->actionName     = $ctx->getAction();
        
       //$this->controllerName = $controllerName;
       //$this->actionName     = $actionName;
       $this->params         = $params;
    }

    public function __call( $method, $args )
    {
       // Es una accion?
       if (method_exists($this, $method . 'Action'))
       {
          return $this->{$method . 'Action'}( $args );
       }

       throw new Exception('La accion <b>' . $method . '</b> no existe.');
    }

    public function addToFlash( $key, $value )
    {
    	 $this->flash[$key] = $value;
    }

    public function getFlash($key = NULL)
    {
       if ($key)
          return ( (isset($this->flash[$key])) ?  $this->flash[$key]: NULL);
       else
       	 return $this->flash;
    }
    
    public function getParams()
    {
       return $this->params;
    }
    
    public function addToParams( $params )
    {
       $this->params = new ArrayObject( array_merge((array)$this->params, $params) );
    }

    public function renderString( $string ) // FIXME: puedo crearlo sin pasarle los params, xq es un atributo mio.
    {
       return ViewCommand::display_string( $string );
    }

   /**
    * @param String view nombre de la vista a mostrar. Se busca entre las vistas del componente y el controller actuales.
    */
    public function render( $view )
    {
       return ViewCommand::display( $view, $this->params, $this->flash );
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
    
    
    // ==========================================================================
    // CRUD dinamico.
    
    /* Index deberia declararse en los controllers si o si.
    public function index()
    {
       return $this->listAction();
    }
    */
    
   public function listAction()
   {
      if ( !isset($this->params['max']) ) // paginacion
      {
         $this->params['max'] = 10;
         $this->params['offset'] = 0;
      }

      $context = YuppContext::getInstance();
      $clazz = String::firstToUpper( $context->getController() );
      eval ('$list  = '. $clazz .'::listAll( $this->params );');
      eval ('$count = '. $clazz .'::count();');

      $this->params['class'] = $clazz;
      $this->params['list']  = $list;
      $this->params['count'] = $count;

      return $this->render("list");
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

      return $this->render("show");
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

      if ( isset($this->params['doit']) ) // create
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

         return $this->redirect( array('action'=>'show', 'params'=>array('id'=>$obj->getId())) );
      }

      // create
      $this->params['object'] = $obj;
      //return $this->render("create", $this->params);
      return $this->render("create");
   }
   
   public function editAction()
   {
      $context = YuppContext::getInstance();
      $clazz = String::firstToUpper( $context->getController() );

      eval ('$obj = '. $clazz .'::get( $this->params["id"] );');
      $this->params['object'] = $obj;
      
      return;
   }
   
   public function saveAction()
   {
      $context = YuppContext::getInstance();
      $clazz = String::firstToUpper( $context->getController() );
      
      $id  = $this->params['id'];
      eval('$obj = '. $clazz .'::get( $id );');
      $obj->setProperties( $this->params );
       
      if ( !$obj->save() ) // Con validacion de datos!
      {
         $this->params['object'] = $obj;
         return $this->render("edit");
      }

      $this->flash['message'] = "Los datos fueron actualizados"; // FIXME: i18n
      return $this->redirect( array('action' => 'show',
                                    'params' => array('id' => $obj->getId()) ));
   }
   
   public function deleteAction()
   {
      $context = YuppContext::getInstance();
      $clazz = String::firstToUpper( $context->getController() );
      
      $id  = $this->params['id'];
      eval('$ins = '. $clazz .'::get( $id );');
      
      $ins->delete(true); // Eliminacion logica, si fuera fisica tendria que actualizar los links a las entradas, o borrar tambien las entradas del user.
  
      $this->flash['message'] = "Objeto [$id] eliminado."; // FIXME: i18n
      return $this->redirect( array("action" => "list") ); // FIXME: el redirect mata el flash!
   }
}

?>