<?php

class ComentarioController extends YuppController {

    /**
     * Accion estandar para mostrar una pagina.
     */
    public function indexAction()
    {
       $loguedUser = YuppSession::get("user"); // Lo pone en session en el login.
       if ($loguedUser !== NULL)
          return $this->listAction();
       else
          return $this->redirect(array('controller'=>'usuario', 'action'=>'login'));
    }

    /**
     * Mostrar lista de elementos de alguna clase.
     */
    public function listAction()
    {
       // paginacion
       if ( !array_key_exists('max', $this->params) )
       {
          $this->params['max'] = 5;
          $this->params['offset'] = 0;
       }

       $list = Comentario::listAll( $this->params );
       $this->params['list'] = $list;
       $count = Comentario::count();
       $this->params['count'] = $count; // Maximo valor para el paginador.

       //return ViewCommand::display( "comentario/list", $this->params ); // Id NULL para paginas de scaffolding
       return $this->render("comentario/list", &$this->params);
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $this->params['object'] = $obj;

       //return ViewCommand::display( "comentario/show", $this->params ); // Id NULL para paginas de scaffolding
       return $this->render("comentario/show", &$this->params);
    }

    public function editAction()
    {
       $id    = $this->params['id'];
       $obj = Comentario::get( $id );
       $this->params['object'] = $obj;

       //return ViewCommand::display( "comentario/edit", $this->params ); // Id NULL para paginas de scaffolding
       return $this->render("comentario/edit", &$this->params);
    }

    public function saveAction()
    {
    	 $id  = $this->params['id'];
       $obj = Comentario::get( $id );
       $obj->setProperties( $this->params );
       $obj->save();
       
       if ( !$obj->save() ) // Con validacion de datos!
       {
          $this->params['object'] = $obj;
          return $this->render("entradaBlog/edit", &$this->params);
       }

       // show
       $this->params['object'] = $obj;
       //return ViewCommand::display( "comentario/show", $this->params );
       return $this->render("comentario/show", &$this->params);
    }

    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = Comentario::get( $id );
       $ins->delete();

       $this->flash['message'] = "Elemento [Comentario:$id] eliminado.";

       //return ViewCommand::execute( 'blog', 'comentario', 'list' ); // ($component, $controller, $action)
       return $this->redirect( array("action" => "list") );
    }

    public function createAction()
    {
       $obj = new Comentario(); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

       // View create, que es como edit pero la accion de salvar vuelve aqui.

       if (array_key_exists('doit',$this->params)) // create
       {
          // Setear entrada que se esta comentando.
          $entrada = EntradaBlog::get( $this->params['id'] );
          $obj->setEntrada( $entrada );
          $entrada->addToComentarios( $obj );
          
          $obj->setProperties( $this->params );
          if ( !$obj->save() ) // Con validacion de datos!
          {
             // create
             $this->params['object'] = $obj;
             return $this->render("comentario/create", &$this->params);
          }

          $this->flash['message'] = "Comentario creado con exito.";

          // show (podria hacer redirect pasandole el id)
          return $this->redirect( array("controller" => "entradaBlog", 
                                        "action" => "show", 
                                        "params" => array("id" => $entrada->getId())
                                       ));
       }

       // create
       $this->params['object'] = $obj;
       return $this->render("comentario/create", &$this->params);
    }

}
?>