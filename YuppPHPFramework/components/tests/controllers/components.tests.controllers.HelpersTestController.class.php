<?php

class HelpersTestController extends YuppController {

    private function isTestActionFilter($name)
    {
       return String::endsWith($name,'TestAction');
    }

    function indexAction()
    {
       // array_values xa corregir los indices
       $tests = array_values( array_filter( get_class_methods($this), array($this,"isTestActionFilter") ) );
       
       for ($i=0; $i<count($tests); $i++)
       {
          //echo substr( $tests[$i], 0, strlen($tests[$i])-6); // 6 por A-C-T-I-O-N
          $tests[$i] = substr( $tests[$i], 0, strlen($tests[$i])-6); // 6 por A-C-T-I-O-N
       }
       
       $this->params['tests'] = $tests;
    }
    
    function ajaxLinkTestAction()
    {
       if (isset($this->params['doit']))
       {
          sleep(2); // agregamos demora para ver como carga los comentarios por ajax
          
          header('Content-type: application/json');
          return $this->renderString( "{'mensaje': 'Hola mundo!' }" );
       }
    }
    
    function linkTestAction()
    {
       if (isset($this->params['doit']))
       {
          $this->params['mensaje'] = 'Hola mundo!';
          return;
       }
    }
    
    function imgTestAction()
    {
       
    }
    
    function urlTestAction()
    {
       
    }
}
?>