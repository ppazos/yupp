<?php

class EntradaBlogController extends YuppController {

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
       if ( !array_key_exists('max',$this->params) )
       {
          $this->params['max'] = 5;
          $this->params['offset'] = 0;
       }

       $list = EntradaBlog::listAll( $this->params );
       $this->params['list'] = $list;
       
       $count = EntradaBlog::count();
       $this->params['count'] = $count; // Maximo valor para el paginador.
       
       //return $this->render("entradaBlog/list", &$this->params);
       return $this->render("list", &$this->params);
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $this->params['object'] = $obj;
       //return $this->render("entradaBlog/show", &$this->params);
       return $this->render("show", &$this->params);
    }
    
    public function getCommentsJSONAction()
    {
      $id  = $this->params['id'];
      $entrada = EntradaBlog::get( $id );
      $comentarios = $entrada->getComentarios();
      
      // TODO: este es el toJSON de una lista de objetos!
      $json = "";
      foreach($comentarios as $comentario)
      {
      	$json .= $comentario->toJSON() . ", ";
      }
      
      $json = substr($json, 0, -2);
      
      //header('X-JSON: (' . $json . ')');  
      //header('Content-type: application/x-json');
      
      sleep(2);
      
      header('Content-type: application/json'); // TODO: si la accion tiene sufijo JSON, que lo ponga solo...
    	//return $this->renderString( "{'comentarios':['un comentario', 'otro comentario', 'otro mas']}" );
      return $this->renderString( "{'comentarios':[ $json ]}" );
    }

    public function editAction()
    {
       $id    = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $this->params['object'] = $obj;
       //return $this->render("entradaBlog/edit", &$this->params);
       return $this->params; // Puedo retornar solo los params porque hay una vista llamada edi en entradaBlog.
    }

    public function saveAction()
    {
       Logger::getInstance()->pm_log("EntradaBlogController::saveAction");

      
       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $obj->setProperties( $this->params );
       
       if ( !$obj->save() ) // Con validacion de datos!
       {
          $this->params['object'] = $obj;
          //return $this->render("entradaBlog/edit", &$this->params);
          return $this->render("edit", &$this->params);
       }

       // show
       $this->params['object'] = $obj;
       
       // Con esta accion no puedo retornar solo los params porque no hay vista llamada "save".
       //return $this->render("entradaBlog/show", &$this->params);
       return $this->render("show", &$this->params);
    }

    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = EntradaBlog::get( $id );
       $ins->delete(true); // Eliminacion logica.

       $this->flash['message'] = "Elemento [EntradaBlog:$id] eliminado.";
       return $this->redirect( array("action" => "list") );
    }

    public function createAction()
    {
       $obj = new EntradaBlog(); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

       // View create, que es como edit pero la accion de salvar vuelve aqui.
       if (array_key_exists('doit',$this->params)) // create
       {
          $obj->setProperties( $this->params );
          if ( !$obj->save() ) // Con validacion de datos!
          {
          	 // create
             $this->params['object'] = $obj;
             
             //return $this->render("entradaBlog/create", &$this->params);
             return $this->params;
          }

          $this->flash['message'] = "Entrada creada con exito.";

          // redirect show
          return $this->redirect( array('action' => 'show',
                                        'params' => array('id' => $obj->getId())
                                       ));
       }

       // create
       $this->params['object'] = $obj;
       
       //return $this->render("entradaBlog/create", &$this->params);
       return $this->params;
    }

}
?>