<?php

abstract class TestCase {

   // TestSuite
   private $suite;

   function __construct($suite)
   {
      $this->suite = $suite;
   }
   
   public function assert($cond, $msg = 'Error')
   {
      // TODO: obtener un mensaje que diga mas, linea, clase y
      //       metodo donde se intenta verificar la condicion
      //if (!$cond) $this->suite->report('error');
      
      if (!$cond)
      {
         // http://php.net/manual/en/function.debug-backtrace.php
         
         ob_start(); 
         debug_print_backtrace(); // Stack de llamadas que resultaron en un test que falla
         $trace = ob_get_contents(); 
         ob_end_clean(); 

         // Se quita la llamada a este metodo de el stack (assert)
         $pos = strpos($trace, "\n");
         if ($pos !== false)
         {
            $trace = substr($trace, $pos);
         }
         
         $this->suite->report($msg .': '. $trace);
      }
   }
   
   // A implementar por las subclases
   public abstract function run();
}
?>