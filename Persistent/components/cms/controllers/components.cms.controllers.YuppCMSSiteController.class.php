<?php

class YuppCMSSiteController extends YuppController {

/**
 * Tengo que ver si estoy editando o si solo estoy viendo el sitio.
 * Si estoy editando tengo acceso a las acciones: list, show, edit, create, save, delete.
 * Si estoy viendo tengo acceso a las acciones: display.
 */

    /**
     * Accion estandar para mostrar una pagina.
     */
    public function indexAction()
    {
       return $this->listAction();
    }

    /**
     * Mostrar lista de elementos de alguna clase.
     */
    public function listAction()
    {
       // paginacion
       if ( !$this->params['max'] )
       {
          $this->params['max'] = 5;
          $this->params['offset'] = 0;
       }

       $list = YuppCMSSite::listAll( $this->params );
       $this->params['list'] = $list;
       
       $count = YuppCMSSite::count();
       $this->params['count'] = $count; // Maximo valor para el paginador.
       
       return $this->render("yuppCMSSite/list", &$this->params);
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = YuppCMSSite::get( $id );
       $this->params['object'] = $obj;
       return $this->render("yuppCMSSite/show", &$this->params);
    }

    public function editAction()
    {
       $id  = $this->params['id'];
       $obj = YuppCMSSite::get( $id );
       $this->params['object'] = $obj;

       return $this->params; // Puedo retornar solo los params porque hay una vista llamada edi en page.
    }

    public function saveAction()
    {
       Logger::getInstance()->pm_log("PageController::saveAction");

      
       $id  = $this->params['id'];
       $obj = YuppCMSSite::get( $id );
       $obj->setProperties( $this->params );
       
       if ( !$obj->save() ) // Con validacion de datos!
       {
          $this->params['object'] = $obj;
          return $this->render("yuppCMSSite/edit", &$this->params);
       }

       // show
       $this->params['object'] = $obj;
       
       // Con esta accion no puedo retornar solo los params porque no hay vista llamada "save".
       return $this->render("yuppCMSSite/show", &$this->params);
    }

    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = YuppCMSSite::get( $id );
       $ins->delete(true); // Eliminacion logica.

       $this->flash['message'] = "Elemento [Page:$id] eliminado.";
       return $this->redirect( array("action" => "list") );
    }

    public function createAction()
    {
       $obj = new YuppCMSSite(); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

       // View create, que es como edit pero la accion de salvar vuelve aqui.
       if ($this->params['doit']) // create
       {
          $obj->setProperties( $this->params );
          if ( !$obj->save() ) // Con validacion de datos!
          {
          	 // create
             $this->params['object'] = $obj;
             
             return $this->params;
          }

          $this->flash['message'] = "Entrada creada con exito.";

          // redirect show
          return $this->redirect( array('action' => 'show',
                                        'params' => array('id' => $obj->getId()) ));
       }

       // create
       $this->params['object'] = $obj;
       
       return $this->params;
    }


    /**
     * Muestra la pagina para que la vea el usuario como una pagina web.
     */
    public function displayAction()
    {
       $id  = $this->params['id'];
       $obj = YuppCMSSite::get( $id );
       $this->params['object'] = $obj;
       return $this->render("yuppCMSSite/display", &$this->params);
    }
}
?>