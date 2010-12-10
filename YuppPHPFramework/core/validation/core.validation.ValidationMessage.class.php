<?php

YuppLoader::load('core.validation','Constraints');
YuppLoader::loadScript('core.validation','Messages');
YuppLoader :: load('core.mvc', 'DisplayHelper');

class ValidationMessage {

   const MSG_NULLABLE = "validation.error.nullable";
   const MSG_BLANK = "validation.error.blank";
   const MSG_LOWER = "validation.error.lower";
   const MSG_GREATER = "validation.error.greater";
   const MSG_INLIST = "validation.error.inList";
   const MSG_BETWEEN = "validation.error.between";
   const MSG_MINLENGTH = "validation.error.minLengthConstraint";
   const MSG_MAXLENGTH = "validation.error.maxLengthConstraint";
   const MSG_EMAIL = "validation.error.email";

   public static function getMessage( $constraint, $attr, $value )
   {
      //eval ('$list = ' . $clazz . '::listAll( $this->params );');
      eval ('$msg = self::'.get_class($constraint).'( $constraint, $attr, $value );');
      return $msg;
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
   
   private static function between( $constraint, $attr, $value)
   {
      // 0=attr
      // 1=min
      // 2=max
      $msg = DisplayHelper::message( self::MSG_BETWEEN );
      $msg = str_replace('{0}', $attr, $msg);
      $msg = str_replace('{1}', $constraint->getMin(), $msg);
      return str_replace('{2}', $constraint->getMax(), $msg);
   }
   
   private static function minlengthconstraint( $constraint, $attr, $value)
   {
      // 0=attr
      // 1=min
      $msg = DisplayHelper::message( self::MSG_MINLENGTH );
      $msg = str_replace('{0}', $attr, $msg);
      return str_replace('{1}', $constraint->getValue(), $msg);
   }
   
   private static function maxlengthconstraint( $constraint, $attr, $value)
   {
      // 0=attr
      // 1=min
      $msg = DisplayHelper::message( self::MSG_MAXLENGTH );
      $msg = str_replace('{0}', $attr, $msg);
      return str_replace('{1}', $constraint->getValue(), $msg);
   }
   
   private static function emailconstraint( $constraint, $attr, $value)
   {
      // 0=attr
      $msg = DisplayHelper::message( self::MSG_EMAIL );
      return str_replace('{0}', $attr, $msg);
   }
   
   private static function inlist( $constraint, $attr, $value)
   {
      // 0=attr
      // 1=lista
      $msg = DisplayHelper::message( self::MSG_INLIST );
      $msg = str_replace('{0}', $attr, $msg);
      return str_replace('{1}', print_r($constraint->getList(), true), $msg);
   }
}
?>