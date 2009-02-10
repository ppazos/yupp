<?php

abstract class Condition {

   // Valor de referencia para comparar con el atributo
   protected $referenceValue = NULL;

   //protected $attributeName; // Se le tendria que poder poner de la tabla que es... seria en realidad algo del tipo "projection" o algo como "tableColumn"...

   protected $attribute; // Attribute

   // Si el valor de referencia es otro atributo.
   protected $referenceAttribute = NULL;


   public function setReferenceValue( $val )
   {
      $this->referenceValue = $val;
      $this->referenceAttribute = NULL;
   }

   public function setReferenceAttribute( $attr )
   {
      $this->referenceAttribute = $attr;
      $this->referenceValue = NULL;
   }

   public function setAttribute( $attr )
   {
      $this->attribute = $attr;
   }
   /*
   public function getReferenceValue( )
   {
      return $this->referenceValue;
   }
   */

   public function evaluateAttribute()
   {
      return $this->attribute->alias . "." . $this->attribute->attr ;
   }
   
   public function evaluateReferenceAttribute()
   {
      return $this->referenceAttribute->alias . "." . $this->referenceAttribute->attr ;
   }
   
   public function evaluateReferenceValue()
   {
      //echo "<h1>";
      //print_r($this);
      //echo "</h1>";
      
      // Si es 0 me devuelve null...
      if ( $this->referenceValue === 0 )
      {
         //echo "es cero";
         return "0";
      }
      
   	return (is_string($this->referenceValue)) ? "'" . $this->referenceValue . "'" : $this->referenceValue;
   }


   public abstract function evaluate($humanReadable = false);


   // Fabricas de condiciones conocidas
   public static function EQ( $alias, $attr, $refValue )
   {
      return CompareCondition::createARV( $alias, $attr, $refValue, CompareCondition::EQUALS );
   }
   
   public static function EQA( $alias, $attr, $alias2, $attr2 )
   {
      return CompareCondition::createAA( $alias, $attr, $alias2, $attr2, CompareCondition::EQUALS );
   }

   public static function GT( $alias, $attr, $refValue )
   {
      return CompareCondition::createARV( $alias, $attr, $refValue, CompareCondition::GT );
   }
   
   public static function GTA( $alias, $attr, $alias2, $attr2 )
   {
      return CompareCondition::createARV( $alias, $attr, $alias2, $attr2, CompareCondition::GT );
   }


   // Fabricas de condiciones complejas
   public static function _OR()
   {
      return new BinaryInfixCondition( BinaryInfixCondition::OP_OR );
   }

   public static function _AND()
   {
      return new BinaryInfixCondition( BinaryInfixCondition::OP_AND );
   }

/*
   public static function _NOT()
   {
      return new NotCondition();
   }
*/
   
}

?>