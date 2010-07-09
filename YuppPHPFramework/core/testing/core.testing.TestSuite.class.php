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
           $this->reports[] = $e->getMessage();
        }
      }
   }
   
   public function report($msg)
   {
      $this->reports[] = $msg;
   }
   
   public function getReports()
   {
      return $this->reports;
   }
   
}

?>