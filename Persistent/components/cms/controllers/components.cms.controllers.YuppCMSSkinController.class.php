<?php

class YuppCMSSkinController extends YuppController {

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

       $list = YuppCMSSkin::listAll( $this->params );
       $this->params['list'] = $list;
       
       $count = YuppCMSSkin::count();
       $this->params['count'] = $count; // Maximo valor para el paginador.
       
       return $this->render("yuppCMSSkin/list", &$this->params);
    }

    public function showAction()
    {
       $id  = $this->params['id'];
       $obj = YuppCMSSkin::get( $id );
       $this->params['object'] = $obj;
       return $this->render("yuppCMSSkin/show", &$this->params);
    }

    public function editAction()
    {
       $this->params['skin'] = YuppCMSSkin::get( $this->params['id'] );

       return $this->params; // Puedo retornar solo los params porque hay una vista llamada edi en page.
    }

    public function saveAction()
    {
       //Logger::getInstance()->pm_log("PageController::saveAction");
       //Logger::struct( $this->params );
      
       $id   = $this->params['id'];
       $skin = NULL; // Se crea o carga.
       
       if ( $id === NULL ) // Si no viene id es que estoy haciendo create.
       {
          $skin         = new YuppCMSSkin ( array("name" => $this->params['skin_name']) );
          
          $templatePage = new TemplatePage( array(
                                             "name"  => $this->params['template_page_name'],
                                             "title" => $this->params['template_page_title'] ) );
                                           
          $skin->setTemplatePage( $templatePage );
       }
       else
       {
          $skin = YuppCMSSkin::get( $id );
       }
       
       
       // Recorro las zonas de la templatePage
       for ($i=0; $i<count($this->params['zone_id']); $i++)
       {
          $zone = new TemplateZone( array(
                                      'name'  =>$this->params['zone_name'][$i],
                                      'posX'  =>$this->params['zone_posX'][$i],
                                      'posY'  =>$this->params['zone_posY'][$i],
                                      'width' =>$this->params['zone_width'][$i],
                                      'height'=>$this->params['zone_height'][$i] ) );
                                  
          $skin->getTemplatePage()->addToTemplateZones( $zone );
       }
       
       
       //Logger::struct( $skin );
       
       
       //$obj->setProperties( $this->params );
       
       if ( !$skin->save() ) // Con validacion de datos!
       {
          $this->params['object'] = $skin;
          $this->params['id'] = $skin->getId();
          return $this->render("yuppCMSSkin/edit", &$this->params);
       }

       // show
       $this->params['object'] = $skin;
       
       // Con esta accion no puedo retornar solo los params porque no hay vista llamada "save".
       return $this->render("yuppCMSSkin/show", &$this->params);
    }

    public function deleteAction()
    {
       $id  = $this->params['id'];
       $ins = YuppCMSSkin::get( $id );
       $ins->delete(true); // Eliminacion logica.

       $this->flash['message'] = "Elemento [Page:$id] eliminado.";
       return $this->redirect( array("action" => "list") );
    }

    public function createAction()
    {
       $obj = new YuppCMSSkin(); // Crea instancia para mostrar en la web los valores por defecto para los atributos que los tengan.
       $obj->setTemplatePage( new TemplatePage() );

       /* Salva con save.
       // View create, que es como edit pero la accion de salvar vuelve aqui.
       if ($this->params['doit']) // create
       {
          $obj->setProperties( $this->params );
          if ( !$obj->save() ) // Con validacion de datos!
          {
          	 // create
             $this->params['skin'] = $obj;
             
             return $this->render("yuppCMSSkin/edit", &$this->params); // Reutilizo edit como vista de create
          }

          $this->flash['message'] = "Entrada creada con exito.";

          // redirect show
          return $this->redirect( array('action' => 'show',
                                        'params' => array('id' => $obj->getId()) ));
       }
       */
       
       // create
       $this->params['skin'] = $obj;
       
       return $this->render("yuppCMSSkin/edit", &$this->params); // Reutilizo edit como vista de create
    }

}
?>