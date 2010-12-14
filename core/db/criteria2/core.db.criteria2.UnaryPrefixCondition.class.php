<?php

class UnaryPrefixCondition extends ComplexCondition {

    const OP_NOT = "NOT"; // PUEDE DEPENDER DEL DBMS.

    private $_exp_group_open  = "(";
    private $_exp_group_close = ")";

    function UnaryPrefixCondition() {
    }
    
    
   public function add( Condition $cond )
   {
      // CHEK 1: NOT Solo admite una condicion!
      if ( count($this->conditions) == 1 ) throw new Exception("Not solo admite una condicion");
      $this->conditions[] = $cond;
      return $this; // Para poder encadenar llamadas...
   }

   public function evaluate( $humanReadable = false )
   {
      // CHEK 1: NOT DEBE tener una y solo una condicion, ya se verifico en el addCond que no se agreguen mas de 1 cond, ahora hay que ver que haya alguna.
      if ( count($this->conditions) == 0 ) throw new Exception("Not debe tener alguna condicion para evaluarse");

      return $this->OP_NOT . " " . $this->_exp_group_open . $this->conditions[0]->evaluate() . $this->_exp_group_close;
   }
}
?>