<?php

class Condition {


   // Tipo de condicion. Se utiliza un campo para no tener tantas subclases.
   private $type;
   
   // Atributo de condicion compleja
   // Lista de subcondiciones de una condicion compleja (AND, OR, NOT).
   // Para NOT, la lista tendra solo una condicion.
   private $subconditions;
   
   // La condicion simple tiene una de estas 2 formas:
   // - attribute OP referenceValue     // Atributo comparado con valor dado
   // - attribute OP referenceAttribute // Atributo comparado con otro atributo
   
   // Atributo de condicion simple
   // Valor de referencia para comparar con el atributo
   private $referenceValue = NULL;

   // Atributo de condicion simple
   // Si el valor de referencia es otro atributo.
   private $referenceAttribute = NULL;
   
   // Atributo de condicion simple
   // Atributo sobre el cual se verifica la condicion
   private $attribute;

   // Tipos de condiciones ===================================================================================
   //
   // Igualdad - desigualdad
   const TYPE_EQ    = "condition.type.equals"; // Dependiendo del DBMS podria entregar regisros donde el valor no es exactamente igual, en MySQL los caracteres con tilde tienen el mismo valor que sin tilde.
   const TYPE_EEQ   = "condition.type.exact"; // Igual que EQ pero se asegura de que los valores sean exactamente iguales.
   const TYPE_NEQ   = "condition.type.not_equals"; // En MySQL dos strings iguales pero uno con una vocal con tilde y el otro sin tilde son considerados como iguales.
   const TYPE_ENEQ  = "condition.type.exact_not_equals"; // Se asegura de que si difiere un solo caracter en un string, sea considerado como distintos.
   
   // Condiciones de "parecidos"
   const TYPE_LIKE  = "condition.type.like";
   const TYPE_ILIKE = "condition.type.ilike";
   
   // Condiciones de ordenamiento
   const TYPE_GT    = "condition.type.greater_than";
   const TYPE_LT    = "condition.type.lower_than";
   const TYPE_GTEQ  = "condition.type.greater_than_or_equals";
   const TYPE_LTEQ  = "condition.type.lower_than_or_equals";
   
   // Condiciones complejas
   const TYPE_NOT   = "condition.type.not";
   const TYPE_AND   = "condition.type.and";
   const TYPE_OR    = "condition.type.or";
   //
   // /Tipos de condiciones ===================================================================================
   
   
   /**
    * Retorna true si la condicion o alguna de sus subcondiciones, tiene una condicion para el atributo $attr.
    * La principal funcionalidad es para PM, para que en las consultas por condicion sepa si hay ya una condicion
    * para deleted (atributo inyectado), porque por defecto se hace deleted=false (condicion inyectada).
    */
   public function hasCondForAttr($attr)
   {
      //echo "hasCondForAttr ".$this->type."<br/>";
      //echo $this->attribute->attr .'<br/>';
      // Si no tiene attribute, es condicion compleja (AND, OR o NOT)
      if (isset($this->attribute) && $this->attribute->attr == $attr)
      {
         //echo "Tiene attribute<br/>";
         return true;
      }
      else
      {
         //echo "No tiene el attribute<br/>";
      }
      
      if (is_array($this->subconditions))
      {
         foreach ($this->subconditions as $cond)
         {
            if ($cond->hasCondForAttr($attr)) return true; // Recursiva
         }
      }
      
      return false;
   }
   
   /**
    * Retorna la primer condicion que encuentre para el atributo attr.
    * Si no encuentra, retorna NULL.
    */
   public function getCondForAttr($attr)
   {
      if (isset($this->attribute) && $this->attribute->attr == $attr) return $this;
      if (is_array($this->subconditions))
      {
         foreach ($this->subconditions as $cond)
         {
            if (($rcond = $cond->getCondForAttr($attr)) !== NULL) return $rcond; // Recursiva
         }
      }
      
      return NULL;
   }
   
   /**
    * Retorna todos los tipos de condiciones existentes.
    * Por ahora se utiliza solamente para verificar que el tipo que se pasa en 'setType()' es correcto.
    */
   private static function getTypes()
   {
      return array(
                self::TYPE_EQ,
                self::TYPE_EEQ,
                self::TYPE_NEQ,
                self::TYPE_ENEQ,
                self::TYPE_LIKE,
                self::TYPE_ILIKE,
                self::TYPE_GT,
                self::TYPE_LT,
                self::TYPE_GTEQ,
                self::TYPE_LTEQ,
                self::TYPE_NOT,
                self::TYPE_AND,
                self::TYPE_OR
             );
   } 
   
   /**
    * Establece el tipo de condicion.
    */
   public function setType( $type )
   {
      if ( !in_array( $type, self::getTypes() ) ) throw new Exception("El tipo de condicion no es correcto. " .__FILE__." ".__LINE__);
      $this->type = $type;
   }
   
   /**
    * Retorna el tipo de condicion.
    */
   public function getType()
   {
      return $this->type;
   }
   
   // Fabricas de condiciones complejas ========================================================
   //
   /**
    * Genera el AND de 2 o mas condiciones.
    */
   public static function _AND()
   {
      //if (!is_array($conditionList)) throw new Exception("'conditionList' debe ser un array y es un ". gettype($conditionList) ." ".__FILE__." ".__LINE__);
      
      $c = new Condition();
      $c->setType( self::TYPE_AND );
      return $c;
   }
   
   /**
    * Genera el OR de 2 o mas condiciones.
    */
   public static function _OR()
   {
      //if (!is_array($conditionList)) throw new Exception("'conditionList' debe ser un array y es un ". gettype($conditionList) ." ".__FILE__." ".__LINE__);
      
      $c = new Condition();
      $c->setType( self::TYPE_OR );
      return $c;
   }
   
   /**
    * Genera el NOT de una condicion.
    */
   public static function _NOT( Condition $condition )
   {
      $c = new Condition();
      $c->setType( self::TYPE_NOT );
      $c->setSubconditions( array($condition) ); // Una sola condicion, puede ser simple o compleja.
      return $c;
   }
   
   /**
    * Agrega una subcondicion a una condicion AND u OR.
    */
   public function add( Condition $cond )
   {
      $this->subconditions[] = $cond;
      return $this;
   }
   
   //
   // /Fabricas de condiciones complejas ========================================================

   
   // Metodos de condiciones complejas ==========================================================
   //
//   public function setSubconditions( $conditionList )
//   {
//      $this->subconditions = $conditionList;
//   }
   
   public function getSubconditions()
   {
      return $this->subconditions;
   }
   
//   public function addSubcondition( Condition $cond )
//   {
//      $this->subconditions[] = $cond;
//   }
   //
   // /Metodos de condiciones complejas =========================================================


   // Metodos de condiciones simples ============================================================
   //
   public function setReferenceValue( $val )
   {
      $this->referenceValue = $val;
      $this->referenceAttribute = NULL;
   }
   public function getReferenceValue()
   {
      return $this->referenceValue;
   }

   public function setReferenceAttribute( $alias, $attr )
   {
      $at = new stdClass();
      $at->alias = $alias;
      $at->attr  = $attr;
      $this->referenceAttribute = $at;
      $this->referenceValue = NULL;
   }
   public function getReferenceAttribute()
   {
      return $this->referenceAttribute;
   }
   
   public function setAttribute( $alias, $attr )
   {
      $at = new stdClass();
      $at->alias = $alias;
      $at->attr  = $attr;
      $this->attribute = $at;
   }
   public function getAttribute()
   {
      return $this->attribute;
   }
   // /Metodos de condiciones simples ===========================================================

   // Fabricas de condiciones simples
   //
   
   /**
    * Condicion de igualdad.
    */
   public static function EQ( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_EQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de desigualdad. 
    */
   public static function NEQ( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_NEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de igualdad exacta.
    */
   public static function EEQ( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_EEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de desigualdad exacta.
    */
   public static function ENEQ( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_ENEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de similitud.
    */
   public static function LIKE( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_LIKE );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de similitud sin considerar mayusculas y minusculas.
    */
   public static function ILIKE( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_ILIKE );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de mayor que.
    */
   public static function GT( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_GT );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de menor que.
    */
   public static function LT( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_LT );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de mayor o igual que.
    */
   public static function GTEQ( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_GTEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de menor o igual que.
    */
   public static function LTEQ( $alias, $attr, $refValue )
   {
      $c = new Condition();
      $c->setType( self::TYPE_LTEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceValue( $refValue );
      return $c;
   }
   
   /**
    * Condicion de igualdad sobre un atributo.
    */
   public static function EQA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_EQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de desigualdad sobre un atributo.
    */
   public static function NEQA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_NEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de igualdad exacta sobre un atributo.
    */
   public static function EEQA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_EEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de desigualdad exacta sobre un atributo.
    */
   public static function ENEQA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_ENEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de similitud sobre un atributo.
    */
   public static function LIKEA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_LIKE );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de similitud sin considedrar mayusculas y minusculas sobre un atributo.
    */
   public static function ILIKEA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_ILIKE );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de mayor que sobre un atributo.
    */
   public static function GTA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_GT );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de menor que sobre un atributo.
    */
   public static function LTA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_LT );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de mayor o igual que sobre un atributo.
    */
   public static function GTEQA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_GTEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }
   
   /**
    * Condicion de menor o igual que sobre un atributo.
    */
   public static function LTEQA( $alias, $attr, $refAlias, $refAttr )
   {
      $c = new Condition();
      $c->setType( self::TYPE_LTEQ );
      $c->setAttribute( $alias, $attr );
      $c->setReferenceAttribute( $refAlias, $refAttr );
      return $c;
   }


/*
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

   // VERIFY: como se usa "between" ?

   // Fabricas de condiciones conocidas
   public static function EQ( $alias, $attr, $refValue )
   {
      return CompareCondition::createARV( $alias, $attr, $refValue, CompareCondition::EQUALS );
   }
   
   public static function STREQ( $alias, $attr, $refValue )
   {
      return CompareCondition::createARV( $alias, $attr, $refValue, CompareCondition::STREQ );
   }
   
   public static function NEQ( $alias, $attr, $refValue )
   {
      return CompareCondition::createARV( $alias, $attr, $refValue, CompareCondition::NOTEQUALS );
   }
   
   public static function EQA( $alias, $attr, $alias2, $attr2 )
   {
      return CompareCondition::createAA( $alias, $attr, $alias2, $attr2, CompareCondition::EQUALS );
   }
   
   public static function STREQA( $alias, $attr, $alias2, $attr2 )
   {
      return CompareCondition::createAA( $alias, $attr, $alias2, $attr2, CompareCondition::STREQ );
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
   */
   

/*
   public static function _NOT()
   {
      return new NotCondition();
   }
*/
   
}

?>