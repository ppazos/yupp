<?php

/**
 * @author Pablo Pazos Gutierrez (pablo.swp@gmail.com)
 */

YuppLoader::load('helloWorld.model', 'Persona');

class PersonaController extends YuppController {

    public function indexAction()
    {
        return $this->listAction();
    }
    
    public function showXMLAction()
    {
        $persona = Persona::get( $this->params['id'] );
        
        YuppLoader::load('core.persistent.serialize', 'XMLPO');
        return $this->renderString( XMLPO::toXML($persona, true) );
        
        //return $this->renderString( $persona->toXML(true) );
    }
    
    public function showJSONAction()
    {
        $persona = Persona::get( $this->params['id'] );
        
        YuppLoader::load('core.persistent.serialize', 'JSONPO');
        return $this->renderString( JSONPO::toJSON($persona, true) );
        
        //return $this->renderString( $persona->toXML(true) );
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