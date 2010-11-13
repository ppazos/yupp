<?php

class BinaryInfixCondition extends ComplexCondition {

   const OP_AND = "AND"; // PUEDE DEPENDER DEL DBMS
   const OP_OR  = "OR"; // PUEDE DEPENDER DEL DBMS

   private $operator;

   function BinaryInfixCondition( $op )
   {  
      // CHECK 1: operador valido.
      
      $this->operator = $op;
   }
    
   public function evaluate( $humanReadable = false )
   {
      // CHECK 1: deberia haber por lo menos 2 condiciones.
      
      $res = "(";
      $i = 0;
      $condCount = count( $this->conditions );

      foreach ( $this->conditions as $cond )
      {
         $res .= $cond->evaluate();
         if ($i+1 < $condCount) $res .= " " . $this->operator . " ";
         //if ($i+1 < $condCount) $res .= " " . $this->mySQL_AND_op . " " . (( $humanReadable ) ? "<br />" : "");
         $i++;
      }

      return $res . ")";
   }
}
?>