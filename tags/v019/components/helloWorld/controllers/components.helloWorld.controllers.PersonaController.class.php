<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */
 
class PersonaController extends YuppController {

    public function indexAction()
    {
       return $this->listAction();
    }
    
    public function showXMLAction()
    {
       $persona = Persona::get( $this->params['id'] );
       return $this->renderString( $persona->toXML(true) );
    }
}

?>