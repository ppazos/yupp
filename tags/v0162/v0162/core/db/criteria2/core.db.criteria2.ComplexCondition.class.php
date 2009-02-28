<?php

abstract class ComplexCondition extends Condition {

    protected $conditions; // Codition collection, luego son evaluadas segun la condicion especifica.

    public function __construct()
    {
      $this->conditions = array();
    }

    public function add( Condition $cond )
    {
       $this->conditions[] = $cond;
       return $this; // Para poder encadenar llamadas...
    }
}

?>