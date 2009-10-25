<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
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

       return $this->render("list");
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $this->params['object'] = $obj;

       return $this->render("show");
    }

    public function editAction()
    {
       $id    = $this->params['id'];
       $obj = Comentario::get( $id );
       $this->params['object'] = $obj;

       return $this->render("edit");
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
          return $this->render("edit");
       }

       // show
       $this->params['object'] = $obj;
       return $this->render("show");
    }

    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = Comentario::get( $id );
       $ins->delete(); // TODO: si es delete fisico y no se pudo eliminar por alguna restriccion, devolver un mensaje en lugar de tirar un error de PHP.
       $this->flash['message'] = "Elemento [Comentario:$id] eliminado.";

       return $this->redirect( array("action" => "list") );
    }

    public function createAction()
    {
       $obj = new Comentario(); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

       // View create, que es como edit pero la accion de salvar vuelve aqui.

       if (isset($this->params['doit'])) // create
       {
          // Setear entrada que se esta comentando.
          $entrada = EntradaBlog::get( $this->params['id'] );
          $obj->setEntrada( $entrada );
          $obj->setProperties( $this->params );
          
          //print_r($entrada);
          
          if ( !$obj->validate() ) // Validacion de datos!
          {
             // create
             $this->params['object'] = $obj;
             return $this->render("create");
          }
          
          $entrada->addToComentarios( $obj ); // FIXME: esto ya deberia salvar!

          if (!$entrada->save()) // Salva comentarios en cascada
          {
             $this->flash['Hubo un problema al actualizar la entrada'];
             $this->params['object'] = $obj;
             return $this->render("create");
          }

          $this->flash['message'] = "Comentario creado con exito.";

          // show (podria hacer redirect pasandole el id)
          return $this->redirect( array( "controller" => "entradaBlog", 
                                         "action" => "show", 
                                         "params" => array("id" => $entrada->getId()) ) );
       }

       // create
       $this->params['object'] = $obj;
       return $this->render("create");
    }

}
?>