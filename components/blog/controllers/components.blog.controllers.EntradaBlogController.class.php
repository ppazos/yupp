<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
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
       if ( !isset($this->params['max']) ) // paginacion
       {
          $this->params['max'] = 5;
          $this->params['offset'] = 0;
       }

       $list = EntradaBlog::listAll( $this->params );
       $this->params['list'] = $list;
       $count = EntradaBlog::count();
       $this->params['count'] = $count; // Maximo valor para el paginador.
       
       return;
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $this->params['object'] = $obj;
       return;
    }
    
    public function showXMLAction()
    {
       $obj = EntradaBlog::get( $this->params['id'] );
       header ("content-type: text/xml");
       return $this->renderString( $obj->toXML(true) );
    }
    
    public function getCommentsJSONAction()
    {
      $id = $this->params['id'];
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
      
      sleep(1); // agregamos demora para ver como carga los comentarios por ajax
      
      header('Content-type: application/json'); // TODO: si la accion tiene sufijo JSON, que lo ponga solo...
      return $this->renderString( "{'comentarios':[ $json ]}" );
    }

    public function editAction()
    {
       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $this->params['object'] = $obj;
       return;
    }

    public function saveAction()
    {
       Logger::getInstance()->pm_log("EntradaBlogController::saveAction");

       $id  = $this->params['id'];
       $obj = EntradaBlog::get( $id );
       $obj->setProperties( $this->params );
       
       $user = YuppSession::get("user");
       $obj->setUsuario( $user );
       
       if ( !$obj->save() ) // Con validacion de datos!
       {
          $this->params['object'] = $obj;
          return $this->render("edit");
       }

       // show
       $this->params['object'] = $obj;
       
       // Con esta accion no puedo retornar solo los params porque no hay vista llamada "save".
       return $this->render("show");
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
       if (isset($this->params['doit'])) // create
       {
          $obj->setProperties( $this->params );
          
          $user = YuppSession::get("user");
          $obj->setUsuario( $user );
       
          if ( !$obj->save() ) // Con validacion de datos!
          {
          	 // create
             $this->params['object'] = $obj;
             
             return;
          }

          $this->flash['message'] = "Entrada creada con exito.";

          // redirect show
          return $this->redirect( array( 'action' => 'show',
                                         'params' => array('id' => $obj->getId()) ) );
       }

       // create
       $this->params['object'] = $obj;

       return;
    }

}
?>