<?php

include_once('./core.testing.TestSuite.class.php');
include_once('./core.testing.TestCase.class.php');

class CustomTestCase extends TestCase {
   
   public function run()
   {
      $this->test1();
      $this->test2();
   }
   
   public function test1()
   {
      $this->assert(false, 'Testing de false');
   }
   
   public function test2()
   {
      throw new Exception("esto es una excepcion");
   }
}

$suite = new TestSuite();
$tc = new CustomTestCase($suite);
$suite->addTestCase($tc);

$suite->run();
print_r( $suite->getReports() );

?>