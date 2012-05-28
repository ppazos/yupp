<?php

class TestSuite {

   // Casos de test a verificar
   private $testCases; // = array();
   
   // Reportes de resultados del test
   private $reports = array();

   function __construct($testCases = array())
   {
      $this->testCases = $testCases;
   }
   
   public function addTestCase($tc)
   {
      $this->testCases[] = $tc;
   }
   
   public function run()
   {
      foreach ( $this->testCases as $testCase )
      {
         try
         {
           $testCase->run();   
         }
         catch (Exception $e)
         {
           ob_start(); 
           debug_print_backtrace(); // Stack de llamadas que resultaron en un test que falla
           $trace = ob_get_contents();
           $moreInfo = ob_get_contents(); // Todos los echos y prints que se pudieron hacer
           ob_end_clean(); 
   
           // Se quita la llamada a este metodo de el stack (assert)
           $pos = strpos($trace, "\n");
           if ($pos !== false)
           {
              $trace = substr($trace, $pos);
           }
           
           // TODO: hay que remover las ultimas lineas que son llamadas del framework
           /*
            * #4  CoreController->testAppAction(Array ()) called at [C:\wamp\www\YuppPHPFramework\core\mvc\core.mvc.YuppController.class.php:59]
#5  YuppController->__call(testApp, Array ())
#6  CoreController->testApp() called at [C:\wamp\www\YuppPHPFramework\core\routing\core.routing.Executer.class.php:163]
#7  Executer->execute() called at [C:\wamp\www\YuppPHPFramework\core\web\core.web.RequestManager.class.php:158]
#8  RequestManager::doRequest() called at [C:\wamp\www\YuppPHPFramework\index.php:94]
            */
          
           $this->report(get_class($testCase), 'EXCEPTION', $e->getMessage(), $trace, $moreInfo);
         }
      }
   }
   
   public function report($test, $type, $msg, $trace = '', $moreInfo = '', $params = array())
   {
      // Esto se podria poner en la vista
      // Muestra variables con valor y tipo
      $_params = '';
      foreach ($params as $key=>$value)
      {
         $_params .= $key.'='.$value.'('.gettype($value).')'."\n";
      }
      
      //$this->reports[] = array('type'=>$type, 'msg'=>$msg, 'trace'=>$trace, 'moreInfo'=>$moreInfo, 'params'=>$_params);
      if (!isset($this->reports[$test])) $this->reports[$test] = array();
      $this->reports[$test][] = array('type'=>$type, 'msg'=>$msg, 'trace'=>$trace, 'moreInfo'=>$moreInfo, 'params'=>$_params);
   }
   
   public function getReports()
   {
      return $this->reports;
   }
}

?>