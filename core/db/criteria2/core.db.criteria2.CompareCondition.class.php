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
   // Estas condiciones son directas, hay otras que se deben verificar invocando una funcion, como STRCMP de MySQL.
   const EQUALS    = "="; // FIXME: en MySQL se debe comparar con STRCMP porque = es case insensitive (http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#function_strcmp) 
   const NOTEQUALS = "<>";
   const GT        = ">";
   const LT        = "<";
   const GE        = ">=";
   const LE        = "<=";
   const LIKE      = "LIKE"; // El segundo parametro debe ser un refValue con una patter del tipo "%dddd%"
   const ILIKE     = "LIKE";

   // Condiciones que se verifican invocando a una funcion del DBMS.
   const STREQ    = "STRCMP=0";

   private $op;

   public function __construct() { }
   
   /**
    *Crea instancia para comparar 2 atributos.
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
    * Crea instancia para comparar un atributo y un valor de referencia.
    * Si refValue es un string, por ahora me lo tienen que pasar con comillas simples incluidas.
    */
   public static function createARV( $alias1, $attr1, $refValue, $compareFunction )
   {
      $c = new CompareCondition();
      
      $a1 = new stdClass();
      $a1->alias = $alias1;
      $a1->attr  = $attr1;
      
      $c->op = $compareFunction;
      $c->attribute      = $a1;
      $c->referenceValue = $refValue;
      
      return $c;
   }
   
   // FIXME: DEPENDE DE MySQL> arreglarlo para que la consulta se genere en el modulo correspondiente (p.e. un condition por DBMS)
   public function evaluate($humanReadable = false)
   {
      // El strcmp con case sensitive se resuelve tambien como LIKE> select 'A' like binary 'a'
      
      if ( $this->referenceValue !== NULL )
      {
         if ( strcmp( $this->op, self::STREQ) == 0 ) return "STRCMP(".$this->evaluateAttribute() .", BINARY(". $this->evaluateReferenceValue().")) = 0"; // FIXME: BINARY es de MySQL.
         
         return $this->evaluateAttribute() . $this->op . $this->evaluateReferenceValue();
      }
      
      if ( $this->referenceAttribute !== NULL )
      {
         if ( strcmp( $this->op, self::STREQ) == 0 ) return "STRCMP(".$this->evaluateAttribute() .", BINARY(". $this->evaluateReferenceAttribute().")) = 0"; // FIXME: BINARY es de MySQL.
         
         return $this->evaluateAttribute() . $this->op . $this->evaluateReferenceAttribute();
      }

      // Si llega aca no le setearon ni referenceValue ni referenceAttribute ...
   }
}

?>