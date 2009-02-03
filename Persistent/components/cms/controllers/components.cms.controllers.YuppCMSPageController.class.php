<?php

class YuppCMSPageController extends YuppController {

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
       // Listado de paginas del sitio $site:
       $site = YuppCMSSite::get( $this->params['site_id'] );
      
       // paginacion
       if ( !$this->params['max'] )
       {
          $this->params['max'] = 5;
          $this->params['offset'] = 0;
       }
       
       $this->params['site'] = $site;

       $ins2 = new YuppCMSPage();
       $tableName = YuppConventions::tableName( YuppCMSPage );
       $condition = Condition::EQ($tableName, "site_id", $this->params['site_id']); // Page->Site(id)=params[id]
       
       //$list = YuppCMSPage::listAll( $this->params );
       $list = YuppCMSPage::findBy( $condition, &$this->params );
       $this->params['list'] = $list;
       
       //$count = YuppCMSPage::count();
       $count = YuppCMSPage::countBy( $condition );
       $this->params['count'] = $count; // Maximo valor para el paginador.
       
       return $this->render("yuppCMSPage/list", &$this->params);
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = YuppCMSPage::get( $id );
       $this->params['page'] = $obj;
       return $this->render("yuppCMSPage/show", &$this->params);
    }

    public function editAction()
    {
       $id  = $this->params['id'];
       $obj = YuppCMSPage::get( $id );
       $this->params['page'] = $obj;
       $this->params['site'] = $obj->getSite();

       return $this->params; // Puedo retornar solo los params porque hay una vista llamada edi en page.
    }

    public function saveAction()
    {
       Logger::getInstance()->pm_log("PageController::saveAction");

       $id  = $this->params['page_id'];
       $obj = YuppCMSPage::get( $id );
       $obj->setProperties( $this->params );
       
       $obj->setLastUpdate(date("Y-m-d H:i:s")); // Ultima modificacion
       
       if ( !$obj->save() ) // Con validacion de datos!
       {
          $this->params['page'] = $obj;
          return $this->render("yuppCMSPage/edit", &$this->params);
       }

       // show
       $this->params['page'] = $obj;
       
       // Con esta accion no puedo retornar solo los params porque no hay vista llamada "save".
       return $this->render("yuppCMSPage/show", &$this->params);
    }

    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = YuppCMSPage::get( $id );
       $ins->delete(true); // Eliminacion logica.

       $this->flash['message'] = "Elemento [Page:$id] eliminado.";
       return $this->redirect( array("action" => "list") );
    }

    public function createAction()
    {
       $obj = new YuppCMSPage(); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.

       $site = YuppCMSSite::get( $this->params['site_id'] );
       $this->params['site'] = $site; // Agrego al modelo el sitio para que quede disponible en la vista.


       // View create, que es como edit pero la accion de salvar vuelve aqui.
       if ($this->params['doit']) // create
       {
          $obj->setProperties( $this->params );
          if ( !$obj->save() ) // Con validacion de datos!
          {
          	 // create
             $this->params['page'] = $obj;
             
             return $this->params;
          }

          $this->flash['message'] = "Entrada creada con exito.";

          // redirect show
          return $this->redirect( array('action' => 'show',
                                        'params' => array('id' => $obj->getId()) ));
       }

       // create
       $this->params['page'] = $obj;
       
       return $this->params;
    }


    /**
     * Muestra la pagina para que la vea el usuario como una pagina web.
     */
    public function displayAction()
    {
       // Deberia poder recibir el nombre de la pagina normalizado a url como _param1 (parte de la url).
      
       $id  = $this->params['id'];
       $obj = YuppCMSPage::get( $id );
       $this->params['object'] = $obj;
       return $this->render("yuppCMSPage/display", &$this->params);
    }
}
?>