<?php

YuppLoader::load('core.validation','Constraints');
YuppLoader::loadScript('core.validation','Messages');
YuppLoader :: load('core.mvc', 'DisplayHelper');

class ValidationMessage {

   const MSG_NULLABLE  = "validation.error.nullable";
   const MSG_BLANK     = "validation.error.blank";
   const MSG_LOWER     = "validation.error.lower";
   const MSG_GREATER   = "validation.error.greater";
   const MSG_INLIST    = "validation.error.inList";
   const MSG_BETWEEN   = "validation.error.between";
   const MSG_MINLENGTH = "validation.error.minLengthConstraint";
   const MSG_MAXLENGTH = "validation.error.maxLengthConstraint";
   const MSG_EMAIL     = "validation.error.email";
   const MSG_DATE      = "validation.error.date";
   const MSG_DATETIME  = "validation.error.datetime";
   const MSG_MATCHES   = "validation.error.matches";

   public static function getMessage( $constraint, $attr, $value )
   {
      // Si es un custom validator creado en la clase de dominio, el self::clase va a fallar,
      // en su lugar, deberia pedirle al validador el codigo del mensaje, y la app deberia
      // definir su Messages para sacar el valor final de ahi.
    
      //eval ('$msg = self::'.get_class($constraint).'( $constraint, $attr, $value );');
      
      $method = get_class($constraint);
         
      if (method_exists('ValidationMessage', $method))
      {
         $msg = self::$method($constraint, $attr, $value);
      }
      else
      {
         // Custom validator class
         // FIXME: por ahora la key del mensaje es el nombre de la clase
         $msg = DisplayHelper::message( get_class($constraint) );
         
         // TODO: poder customizarlo con el atributo y el valor.
      }
      
      return $msg;
   }
   
   private static function matches( $constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=regex
      $msg = DisplayHelper::message( self::MSG_MATCHES );
      
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      return str_replace('{2}', $constraint->getValue(), $msg);
   }
   
   private static function nullable( $constraint, $attr, $value)
   {
      // 0=attr
      $msg = DisplayHelper::message( self::MSG_NULLABLE );
      return str_replace('{0}', $attr, $msg);
   }
   
   private static function blankconstraint( $constraint, $attr, $value)
   {
      // 0=attr
      $msg = DisplayHelper::message( self::MSG_BLANK );
      return str_replace('{0}', $attr, $msg);
   }
   
   private static function minconstraint( $constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=min
      $msg = DisplayHelper::message( self::MSG_GREATER );
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      return str_replace('{2}', $constraint->getValue(), $msg);
   }
   
   private static function maxconstraint( $constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=min
      $msg = DisplayHelper::message( self::MSG_LOWER );
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      return str_replace('{2}', $constraint->getValue(), $msg);
   }
   
   private static function between( $constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=min
      // 3=max
      $msg = DisplayHelper::message( self::MSG_BETWEEN );
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      $msg = str_replace('{2}', $constraint->getMin(), $msg);
      return str_replace('{3}', $constraint->getMax(), $msg);
   }
   
   private static function minlengthconstraint( $constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=min
      $msg = DisplayHelper::message( self::MSG_MINLENGTH );
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      return str_replace('{2}', $constraint->getValue(), $msg);
   }
   
   private static function maxlengthconstraint($constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=min
      $msg = DisplayHelper::message( self::MSG_MAXLENGTH );
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      return str_replace('{2}', $constraint->getValue(), $msg);
   }
   
   private static function emailconstraint($constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      $msg = DisplayHelper::message( self::MSG_EMAIL );
      $msg = str_replace('{0}', $value, $msg);
      return str_replace('{1}', $attr, $msg);
   }
   
   private static function dateconstraint($constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      $msg = DisplayHelper::message( self::MSG_DATE );
      $msg = str_replace('{0}', $value, $msg);
      return str_replace('{1}', $attr, $msg);
   }
   private static function datetimeconstraint($constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      $msg = DisplayHelper::message( self::MSG_DATETIME );
      $msg = str_replace('{0}', $value, $msg);
      return str_replace('{1}', $attr, $msg);
   }
   
   private static function inlist($constraint, $attr, $value)
   {
      // 0=value
      // 1=attr
      // 2=lista
      $msg = DisplayHelper::message( self::MSG_INLIST );
      $msg = str_replace('{0}', $value, $msg);
      $msg = str_replace('{1}', $attr, $msg);
      
      $values = '';
      foreach ($constraint->getList() as $value)
      {
         $values .= $value .', ';
      }
      $values = substr($values, 0, -2);
      
      return str_replace('{2}', $values, $msg);
   }
}
?>