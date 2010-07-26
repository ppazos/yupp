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
    
    public function queryAction()
    {
        $q = new Query();
        $q->addAggregation( SelectAggregation::AGTN_DISTINTC, 'p', 'nombre' )
          ->addFrom('hello_world_persona', 'p')
          ->setCondition(
              Condition::GT('p', "edad", 25)
            );
           
        $pm = PersistentManager::getInstance();
        $result = $pm->findByQuery( $q );
       
        return $this->renderString( print_r($result, true) );
    }
}

?>