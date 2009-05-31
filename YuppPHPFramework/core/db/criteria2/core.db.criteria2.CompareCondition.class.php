<?php

// TODO: permitir que en lugar del alias me pasen nombres de clases. Chekear si es un nombre de clase y transformarlo en el nombre de la tabla.

/**
La idea de esta clase es evitar tener que tener una clase para cada 
funcion binaria de comparacion como =, <, >, etc.
De esta forma se hace una clase sola que recibe como parametro que 
operacion debe usarse para comparar, y dicho parametro es definido 
por la misma clase.
*/
class CompareCondition extends Condition {

   // OBS: pueden depender del DBMS, por ejemplo el ilike no todos lo tienen, en algunos casos hay que poner funciones como LOWER para el attr.
   // Por eso los valores deben tomarse desde archivos externos que definan estos valores para cara dbms, por ahora los dejo para MySQL.
   const EQUALS    = "="; // FIXME: en MySQL se debe comparar con STRCMP porque = es case insensitive (http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#function_strcmp) 
   const NOTEQUALS = "<>";
   const GT        = ">";
   const LT        = "<";
   const GE        = ">=";
   const LE        = "<=";
   const LIKE      = "LIKE"; // El segundo parametro debe ser un refValue con una patter del tipo "%dddd%"
   const ILIKE     = "LIKE";

   private $op;

   public function __construct()
   {
   }
   
   
   /**
   Crea instancia para comparar 2 atributos.
   */
   public static function createAA( $alias1, $attr1, $alias2, $attr2, $compareFunction )
   {
      $c = new CompareCondition();
      
      $a1 = new stdClass();
      $a1->alias = $alias1;
      $a1->attr  = $attr1;

      $a2 = new stdClass();
      $a2->alias = $alias2;
      $a2->attr  = $attr2;
      
      $c->op = $compareFunction;
      $c->attribute          = $a1;
      $c->referenceAttribute = $a2;
      
      return $c;
   }
   
   /**
   Crea instancia para comparar un atributo y un valor de referencia.
   Si refValue es un string, por ahora me lo tienen que pasar con comillas simples incluidas.
   */
   public static function createARV( $alias1, $attr1, $refValue, $compareFunction )
   {
      $c = new CompareCondition();
      
      $a1 = new stdClass();
      $a1->alias = $alias1;
      $a1->attr  = $attr1;
      
      //echo "CompCond.refValue: " . $refValue . "<hr/>";

      $c->op = $compareFunction;
      $c->attribute      = $a1;
      $c->referenceValue = $refValue;
      
      //print_r($c); // OK
      
      return $c;
   }
   

   public function evaluate($humanReadable = false)
   {
      if ( $this->referenceValue !== NULL )
         return $this->evaluateAttribute() . $this->op . $this->evaluateReferenceValue();

      if ( $this->referenceAttribute !== NULL )
         return $this->evaluateAttribute() . $this->op . $this->evaluateReferenceAttribute();

      // Si llega aca no le setearon ni referenceValue ni referenceAttribute ...
   }
}

?>